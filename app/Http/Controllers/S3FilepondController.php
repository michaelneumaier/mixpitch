<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use App\Services\S3FilepondService;

class S3FilepondController extends Controller
{
    /**
     * FilePond ./process route logic.
     *
     * @return \Illuminate\Http\Response
     */
    public function process(Request $request, S3FilepondService $service)
    {
        // Check if chunk upload
        if ($request->hasHeader('upload-length')) {
            return Response::make($service->initChunk(), 200, ['content-type' => 'text/plain']);
        }

        $validator = $service->validator($request, config('filepond.validation_rules', []));

        if ($validator->fails()) {
            return Response::make($validator->errors(), 422);
        }

        return Response::make($service->store($request), 200, ['content-type' => 'text/plain']);
    }

    /**
     * FilePond ./patch route logic.
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function patch(Request $request, S3FilepondService $service)
    {
        return Response::make('Ok', 200)->withHeaders(['Upload-Offset' => $service->chunk($request)]);
    }

    /**
     * FilePond ./head, ./restore route logic.
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function head(Request $request, S3FilepondService $service)
    {
        // If request has patch key, then its a head request
        if ($request->has('patch')) {
            return Response::make('Ok', 200)->withHeaders(['Upload-Offset' => $service->offset($request->patch)]);
        }

        // If request has restore key, then its a restore request
        if ($request->has('restore')) {
            return $service->restore($request->restore);
        }

        return Response::make('', 404);
    }

    /**
     * FilePond ./revert route logic.
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function revert(Request $request, S3FilepondService $service)
    {
        $filepond = $service->retrieve($request->getContent());

        $service->delete($filepond);

        return Response::make('Ok', 200, ['content-type' => 'text/plain']);
    }
}
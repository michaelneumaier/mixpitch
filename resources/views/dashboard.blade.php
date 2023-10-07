@extends('layouts.app')

@section('content')
<div class="container mx-auto py-4">
    <div class="flex justify-center">
        <div class="w-full md:w-9/10 lg:w-4/5">
            <div class="bg-white shadow mb-4">
                <div class="bg-gray-200 py-3 px-4">{{ __('Dashboard') }}</div>

                <div class="p-4">
                    <h3 class="text-2xl font-semibold mb-3">Your Projects</h3>
                    @if ($projects->isEmpty())
                    <p class="mb-3">You haven't shared any projects yet.</p>
                    @else
                    <ul class="divide-y divide-gray-200 mb-3">
                        @foreach ($projects as $project)
                        <div class="bg-cover bg-center"
                            style="background: {{ $project->image_path ? 'url(' . asset('storage/'.$project->image_path) . ')' : 'transparent' }}">
                            <li class="p-3 bg-gray-800 bg-opacity-70">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center justify-between">
                                        <div class="mr-2">
                                            <livewire:status-button :status="$project->status" />
                                        </div>
                                        <h5 class="text-lg font-semibold mb-0">
                                            <a href="{{ route('projects.show', $project) }}"
                                                class="no-underline text-blue-500">
                                                {{ $project->name }}
                                            </a>
                                        </h5>
                                    </div>
                                    <div class="flex space-x-2 float-right">
                                        <form action="{{ route('projects.edit', $project) }}" method="GET" class="mr-2">
                                            @csrf
                                            <button type="submit"
                                                class="text-sm bg-blue-500 text-white px-2 py-1 rounded">
                                                Edit
                                            </button>
                                        </form>
                                        <form action="{{ route('projects.destroy', $project) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-sm bg-red-500 text-white px-2 py-1 rounded">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center">
                                    <div class="flex space-x-3 text-white">
                                        <div>
                                            Files Uploaded: <span class="font-semibold">{{ $project->files->count()
                                                }}</span>
                                        </div>
                                        <div>
                                            Mixes: <span class="font-semibold">{{ $project->mixes->count() }}</span>
                                        </div>
                                        <div>
                                            Last Updated: <span class="font-semibold">{{ $project->updated_at->format('F
                                                j, Y') }}</span>
                                        </div>
                                    </div>

                                </div>
                            </li>
                        </div>
                        @endforeach
                    </ul>
                    @endif
                    <a href="{{ route('projects.upload') }}"
                        class="block bg-blue-500 hover:bg-blue-400 text-2xl text-white text-center font-bold w-full py-2 px-4 border-b-4 border-blue-700 hover:border-blue-500 rounded whitespace-nowrap">Share
                        Your Project</a>
                </div>

                <div class="p-4">
                    <h3 class="text-lg font-semibold mb-3">Your Uploaded Mixes</h3>
                    <div>
                        <ul class="divide-y divide-gray-200">
                            @forelse($mixes as $mix)
                            <li class="py-3 flex justify-between items-center">
                                <a href="{{ route('projects.show', $project) }}" class="text-blue-500 hover:underline">
                                    {{ $mix->project->name }}
                                </a>
                                <span>
                                    @for($i = 1; $i <= 10; $i++) @if($i <=$mix->rating)
                                        ★
                                        @else
                                        ☆
                                        @endif
                                        @endfor
                                </span>
                            </li>
                            @empty
                            <li class="py-3">You haven't uploaded any mixes yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


@endsection
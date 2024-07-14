<div class="bg-base-200/50 rounded-lg p-3 my-2">
    <h3 class="text-xl font-semibold mb-4">Manage Your Pitch</h3>

    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)">
        @if ($message = session('message'))
        <div x-show="show" class="alert alert-success" x-transition>
            {{ $message }}
        </div>
        @endif
    </div>

    @if($pitch->status == 'pending')
    <div class="mt-4">
        <p class="text-gray-500">The project owner must allow access before you can upload files.</p>
    </div>
    @elseif($pitch->status == 'ready_for_review')
    <div class="mt-4">
        <p class="text-gray-500">You have submitted your pitch. We are waiting on the project owner to review your
            pitch.</p>
    </div>
    @elseif($pitch->status == 'pending_review')
    <div class="mt-4">
        <p class="text-gray-500">The project owner wants you to review their response.</p>
    </div>
    @elseif($pitch->status == 'denied')
    <div class="mt-4">
        <p class="text-gray-500">The project owner denied your pitch.</p>
    </div>
    @elseif($pitch->status == 'approved')
    <div class="mt-4">
        <p class="text-gray-500">The project owner has approved your pitch.</p>
    </div>
    @endif

    @if($snapshots->isNotEmpty())
    <div class="mt-4">
        <h4 class="font-semibold">Submitted Pitches</h4>
        <ul class="space-y-2">
            @foreach($snapshots as $snapshot)
            <li class="flex justify-between items-center p-2 bg-gray-100 rounded-lg shadow">
                <span>Version {{ $snapshot->snapshot_data['version'] }} - {{ $snapshot->created_at->format('M d, Y H:i')
                    }}</span>
                <a href="{{ route('pitches.showSnapshot', [$pitch->id, $snapshot->id]) }}"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-2 rounded text-sm">
                    View
                </a>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($events->count())
    <div class="mt-4">
        <h4 class="font-semibold">Pitch History</h4>
        <div class="space-y-2">
            <div class="">
                {{ $events->links(data: ['scrollTo' => false]) }}
            </div>
            @foreach ($events as $event)
            <div class="p-2 bg-gray-50 rounded-sm shadow">
                <div class="flex flex-col items-start w-full text-xl">
                    <div class="flex items-center flex-grow w-full">
                        <img class="h-6 w-6 rounded-full object-cover mr-2" src="{{ $event->user->profile_photo_url }}"
                            alt="{{ $event->user->name }}" />
                        <div class="flex flex-grow justify-between">
                            <div class="text-sm max-w-xs truncate mr-3 font-bold">{{ $event->user->name }}</div>
                            <div class="text-sm flex">
                                @if ($event->event_type == 'comment' && $event->created_by == auth()->id())
                                <div class="text-sm mr-2">
                                    <!-- Tooltip and Trash Icon -->
                                    <button wire:click="deleteComment({{ $event->id }})"
                                        wire:confirm="Are you sure you want to delete this comment? This action cannot be undone."
                                        class="text-red-500 hover:text-red-700 relative">
                                        <i class="fas fa-trash"></i>
                                        <!-- Tooltip -->
                                        <div
                                            class="absolute top-0 left-0 mt-8 p-2 bg-black text-white text-xs rounded shadow-lg hidden group-hover:block">
                                            Delete Comment
                                        </div>
                                    </button>
                                </div>
                                @endif

                                {{ $event->created_at->format('M d, Y H:i') }}
                            </div>
                        </div>


                    </div>
                    <div class="p-2 text-base flex-grow ">

                        @if($event->status == 'pending')
                        <i class="fas fa-hourglass-half text-yellow-400"></i> Pitch Access is Pending
                        @elseif($event->status == 'in_progress')
                        <i class="fas fa-spinner text-blue-400"></i> Pitch In Progress
                        @elseif($event->status == 'ready_for_review')
                        <i class="fas fa-file-alt text-orange-400"></i> Pitch Submitted for Review
                        @elseif($event->status == 'pending_review')
                        <i class="fas fa-undo text-purple-400"></i> Pitch Reviewed by Project Owner and Pending
                        Review from Pitch User
                        @elseif($event->status == 'approved')
                        <i class="fas fa-check-circle text-green-400"></i> Pitch Reviewed by Project Owner and
                        Approved
                        @elseif($event->status == 'denied')
                        <i class="fas fa-times-circle text-red-400"></i> Pitch Reviewed by Project Owner and Denied
                        @endif
                        {{ $event->comment ?? '' }}

                        <p>{{ $event->rating ? 'Rating: ' . $event->rating : '' }}</p>

                    </div>

                </div>
            </div>
            @endforeach
        </div>

    </div>
    @endif

    @if($pitch->status == 'in_progress' || $pitch->status == 'pending_review')
    <div x-data="{ showComment: false, comment: '' }" class="my-4">
        <div class="flex justify-end">
            <template x-if="!showComment">
                <button @click="showComment = true"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                    Add Comment
                </button>
            </template>
        </div>

        <form wire:submit.prevent="submitComment" x-show="showComment" @submit.prevent="showComment = false"
            class="mt-4">
            <div class="mb-4">
                <label for="comment" class="block text-gray-700 hidden">Add Comment</label>
                <textarea id="comment" wire:model.defer="comment" x-model="comment" rows="3"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"></textarea>
                @error('comment') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex justify-end space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                    Submit Comment
                </button>
                <button type="button" @click="showComment = false; comment = ''"
                    class="text-white text-2xl bg-red-500 hover:bg-bg-700 px-2 m-1 rounded">
                    <i class="fas fa-xmark "></i>
                </button>
            </div>
        </form>
    </div>

    <form wire:submit.prevent="submitRating" class="mt-4 hidden">
        <div class="mb-4">
            <label for="rating" class="block text-gray-700">Add Rating</label>
            <input type="number" id="rating" wire:model.defer="rating" min="1" max="5"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
            @error('rating') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
            Submit Rating
        </button>
    </form>
    @endif

    @if($uploadedFiles->count())
    <div class="">
        <h4 class="font-semibold">Uploaded Files</h4>
        <div class="space-y-1">
            @foreach ($uploadedFiles as $file)
            <div class="flex flex-col p-2 bg-gray-100 rounded-lg shadow"
                x-data="{ showNotes: false, note: '{{ $file->note }}' }">
                <div class="flex flex-col md:flex-row justify-between items-end">
                    <span class="flex-1 place-self-start truncate font-bold">{{ $file->file_name }}</span>

                    <div class="flex items-center space-x-2">
                        <template x-if="!showNotes">
                            <a href="#" @click.prevent="showNotes = true"
                                :class="{'border-green-500 text-green-500 hover:border-green-700 hover:text-green-700': !note, 'border-orange-500 text-orange-500 hover:border-orange-700 hover:text-orange-700': note}"
                                class="border py-1 px-2 rounded text-sm">
                                {{ $file->note ? 'Edit Note' : 'Add Note' }}
                            </a>
                        </template>
                        <template x-if="showNotes">
                            <div class="flex items-center space-x-2">
                                <button @click.prevent="showNotes = false; note = '{{ $file->note }}'"
                                    class="bg-red-500 hover:bg-red-700 text-white py-1 px-2 rounded text-sm">
                                    Cancel
                                </button>
                                <button @click.prevent="$wire.saveNote({{ $file->id }}, note); showNotes = false"
                                    class="bg-green-500 hover:bg-green-700 text-white py-1 px-2 rounded text-sm">
                                    Save
                                </button>
                            </div>
                        </template>
                        <a href="{{ route('pitch-files.show', $file) }}"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-semibold py-1 px-2 rounded text-sm">
                            View
                        </a>
                        <a href="{{ asset('storage/' . $file->file_path) }}" download="{{ $file->file_name }}"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-2 rounded text-sm">
                            Download
                        </a>
                        <button wire:click="deleteFile({{ $file->id }})"
                            wire:confirm="Are you sure you want to delete this file?"
                            class="bg-red-500 hover:bg-red-700 text-white font-semibold py-1 px-2 rounded text-sm">
                            Delete
                        </button>
                    </div>
                </div>
                <template x-if="showNotes">
                    <div class="flex">
                        <textarea x-model="note" class="mt-2 block w-full border border-gray-300 rounded-md shadow-sm"
                            rows="1"></textarea>
                    </div>
                </template>
                <div x-show="!showNotes && note" class="text-sm text-gray-700 pl-2">
                    <strong>Note:</strong> {{ $file->note }}
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-4">
            {{ $uploadedFiles->links(data: ['scrollTo' => false]) }}
        </div>
    </div>
    @endif

    @if($pitch->status == 'in_progress' || $pitch->status == 'pending_review')
    <form wire:submit.prevent="uploadFiles" x-data="{ files: [], showUpload: false }" @file-upload-success.window="
            console.log('File upload success');
            files = [];
            showUpload = false;
        " class="mb-4">
        <div class="mb-4 relative">
            <input type="file" wire:model.defer="files" multiple
                class="opacity-0 absolute inset-0 w-full h-full cursor-pointer"
                @change="files = Array.from($event.target.files); showUpload = files.length > 0">
            <div
                class="border border-dashed border-gray-400 p-4 rounded-lg flex flex-col items-center justify-center cursor-pointer">
                <div class="fa fa-upload fa-3x text-gray-400"></div>
                <p class="text-gray-400 mt-2"
                    x-html="files.length > 0 ? files.map(file => `<span class='truncate max-w-xs inline-block'>${file.name}</span>`).join('<br>') : 'Drag and drop files here or click to upload'">
                </p>
            </div>
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded"
            :disabled="files.length === 0" x-show="showUpload" x-transition>
            Upload Pitch Files
        </button>
        <button type="button" class="bg-red-500 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded ml-2"
            :disabled="files.length === 0" x-show="showUpload" x-transition @click="files = []; showUpload = false;">
            Clear Selection
        </button>
    </form>
    @endif

    @if($pitch->status == 'in_progress' || $pitch->status == 'pending_review')
    <div class="mt-4 flex justify-end">
        <button wire:click="submitForReview" wire:confirm="Are you sure you want to Submit your Pitch?"
            class="bg-green-500 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded">
            <i class="fas fa-check pr-2"></i>
            Ready To Submit
        </button>
    </div>
    @elseif($pitch->status != 'pending')
    <div class="mt-4 flex justify-end">
        <button wire:click="cancelPitchSubmission" wire:confirm="Are you sure you want to cancel your Pitch?"
            class="bg-red-500 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">
            <i class="fas fa-xmark pr-2"></i>
            Cancel Submission
        </button>
    </div>
    @endif
</div>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ $isEditMode ? 'Edit User' : 'Create User' }}</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-base-100 shadow-sm rounded-lg overflow-hidden">
        <form wire:submit.prevent="save" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Name</span>
                    </label>
                    <input 
                        type="text" 
                        wire:model.defer="name" 
                        class="input input-bordered w-full @error('name') input-error @enderror" 
                        placeholder="Enter name"
                    />
                    @error('name')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <!-- Email -->
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Email</span>
                    </label>
                    <input 
                        type="email" 
                        wire:model.defer="email" 
                        class="input input-bordered w-full @error('email') input-error @enderror" 
                        placeholder="Enter email"
                    />
                    @error('email')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Password {{ $isEditMode ? '(leave blank to keep current)' : '' }}</span>
                    </label>
                    <input 
                        type="password" 
                        wire:model.defer="password" 
                        class="input input-bordered w-full @error('password') input-error @enderror" 
                        placeholder="{{ $isEditMode ? 'Enter new password' : 'Enter password' }}"
                    />
                    @error('password')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <!-- Password Confirmation -->
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Confirm Password</span>
                    </label>
                    <input 
                        type="password" 
                        wire:model.defer="password_confirmation" 
                        class="input input-bordered w-full" 
                        placeholder="Confirm password"
                    />
                </div>

                <!-- Role -->
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Role</span>
                    </label>
                    <select 
                        wire:model.defer="role" 
                        class="select select-bordered w-full @error('role') select-error @enderror"
                    >
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                    @error('role')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <!-- Avatar -->
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Avatar</span>
                    </label>
                    <input 
                        type="file" 
                        wire:model="avatar" 
                        class="file-input file-input-bordered w-full @error('avatar') file-input-error @enderror" 
                        accept="image/*"
                    />
                    <div wire:loading wire:target="avatar" class="mt-2 text-sm text-gray-500">
                        Uploading...
                    </div>
                    @error('avatar')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>
            </div>

            <!-- Current Avatar Preview -->
            @if($isEditMode && $currentAvatar)
                <div class="mt-6">
                    <h3 class="text-lg font-medium mb-2">Current Avatar</h3>
                    <div class="flex items-center space-x-4">
                        <div class="avatar">
                            <div class="w-24 rounded">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($currentAvatar) }}" alt="{{ $name }}" />
                            </div>
                        </div>
                        <button type="button" wire:click="removeAvatar" class="btn btn-sm btn-outline btn-error">
                            Remove Avatar
                        </button>
                    </div>
                </div>
            @endif

            <!-- Avatar Preview -->
            @if($avatar)
                <div class="mt-6">
                    <h3 class="text-lg font-medium mb-2">Avatar Preview</h3>
                    <div class="avatar">
                        <div class="w-24 rounded">
                            <img src="{{ $avatar->temporaryUrl() }}" alt="Avatar Preview" />
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex justify-between mt-8">
                <a href="{{ route('users.index') }}" class="btn btn-outline">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ $isEditMode ? 'Update User' : 'Create User' }}</span>
                    <span wire:loading wire:target="save">Processing...</span>
                </button>
            </div>
        </form>
    </div>
</div> 
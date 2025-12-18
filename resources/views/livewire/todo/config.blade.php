<div>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold mb-6">Todo Configuration</h1>

        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif

        <div x-data="{ activeTab: 'categories' }" class="space-y-6">
            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button @click="activeTab = 'categories'" :class="activeTab === 'categories' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Categories
                    </button>
                    <button @click="activeTab = 'priorities'" :class="activeTab === 'priorities' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Priorities
                    </button>
                    <button @click="activeTab = 'statuses'" :class="activeTab === 'statuses' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Statuses
                    </button>
                    <button @click="activeTab = 'locations'" :class="activeTab === 'locations' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Locations
                    </button>
                    <button @click="activeTab = 'branches'" :class="activeTab === 'branches' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Branches
                    </button>
                    <button @click="activeTab = 'departments'" :class="activeTab === 'departments' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Departments
                    </button>
                    <button @click="activeTab = 'dueTimes'" :class="activeTab === 'dueTimes' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Due Times
                    </button>
                </nav>
            </div>

            <!-- Categories Tab -->
            <div x-show="activeTab === 'categories'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Todo Categories</h2>

                    <!-- Add New Category -->
                    <form wire:submit.prevent="createCategory" class="mb-4 space-y-2">
                        <div class="flex space-x-2">
                            <input type="text" wire:model="newCategoryName" placeholder="Category Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <textarea wire:model="newCategoryDescription" placeholder="Description" rows="2" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('newCategoryName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @error('newCategoryDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- List Categories -->
                    <ul class="space-y-2">
                        @foreach($categories as $category)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingCategoryId == $category->id)
                                    <form wire:submit.prevent="updateCategory" class="flex flex-1 space-x-2">
                                        <input type="text" wire:model="editingCategoryName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <textarea wire:model="editingCategoryDescription" rows="2" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelCategory" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <div>
                                        <span class="font-medium">{{ $category->name }}</span>
                                        @if($category->description)
                                            <p class="text-sm text-gray-600">{{ $category->description }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <button wire:click="editCategory({{ $category->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="deleteCategory({{ $category->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Priorities Tab -->
            <div x-show="activeTab === 'priorities'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Todo Priorities</h2>

                    <!-- Add New Priority -->
                    <form wire:submit.prevent="createPriority" class="mb-4 space-y-2">
                        <div class="flex space-x-2">
                            <input type="text" wire:model="newPriorityLevel" placeholder="Priority Level" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <input type="number" wire:model="newPriorityRank" placeholder="Rank" class="w-24 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('newPriorityLevel') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @error('newPriorityRank') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- List Priorities -->
                    <ul class="space-y-2">
                        @foreach($priorities as $priority)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingPriorityId == $priority->id)
                                    <form wire:submit.prevent="updatePriority" class="flex flex-1 space-x-2">
                                        <input type="text" wire:model="editingPriorityLevel" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <input type="number" wire:model="editingPriorityRank" class="w-24 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelPriority" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <div>
                                        <span class="font-medium">{{ $priority->level }}</span>
                                        <p class="text-sm text-gray-600">Rank: {{ $priority->rank }}</p>
                                    </div>
                                    <div>
                                        <button wire:click="editPriority({{ $priority->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="deletePriority({{ $priority->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Statuses Tab -->
            <div x-show="activeTab === 'statuses'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Todo Statuses</h2>

                    <!-- Add New Status -->
                    <form wire:submit.prevent="createStatus" class="mb-4 space-y-2">
                        <div class="flex space-x-2">
                            <input type="text" wire:model="newStatusStatus" placeholder="Status Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <textarea wire:model="newStatusDescription" placeholder="Description" rows="2" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                            <input type="text" wire:model="newStatusColorCode" placeholder="Color Code (e.g. #FF0000)" class="w-32 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('newStatusStatus') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @error('newStatusDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @error('newStatusColorCode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- List Statuses -->
                    <ul class="space-y-2">
                        @foreach($statuses as $status)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingStatusId == $status->id)
                                    <form wire:submit.prevent="updateStatus" class="flex flex-1 space-x-2">
                                        <input type="text" wire:model="editingStatusStatus" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <textarea wire:model="editingStatusDescription" rows="2" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                                        <input type="text" wire:model="editingStatusColorCode" class="w-32 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelStatus" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <div>
                                        <span class="font-medium">{{ $status->status }}</span>
                                        @if($status->description)
                                            <p class="text-sm text-gray-600">{{ $status->description }}</p>
                                        @endif
                                        @if($status->color_code)
                                            <div class="flex items-center mt-1">
                                                <div class="w-4 h-4 rounded" style="background-color: {{ $status->color_code }}"></div>
                                                <span class="text-xs ml-1">{{ $status->color_code }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <button wire:click="editStatus({{ $status->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="deleteStatus({{ $status->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Locations Tab -->
            <div x-show="activeTab === 'locations'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Locations</h2>

                    <!-- Add New Location -->
                    <form wire:submit.prevent="createLocation" class="mb-4 space-y-2">
                        <div class="flex space-x-2">
                            <input type="text" wire:model="newLocationName" placeholder="Location Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <textarea wire:model="newLocationAddress" placeholder="Address" rows="2" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('newLocationName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @error('newLocationAddress') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- List Locations -->
                    <ul class="space-y-2">
                        @foreach($locations as $location)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingLocationId == $location->id)
                                    <form wire:submit.prevent="updateLocation" class="flex flex-1 space-x-2">
                                        <input type="text" wire:model="editingLocationName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <textarea wire:model="editingLocationAddress" rows="2" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelLocation" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <div>
                                        <span class="font-medium">{{ $location->name }}</span>
                                        @if($location->address)
                                            <p class="text-sm text-gray-600">{{ $location->address }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <button wire:click="editLocation({{ $location->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="deleteLocation({{ $location->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Branches Tab -->
            <div x-show="activeTab === 'branches'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Branches</h2>

                    <!-- Add New Branch -->
                    <form wire:submit.prevent="createBranch" class="mb-4">
                        <div class="flex">
                            <input type="text" wire:model="newBranchName" placeholder="New Branch Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('newBranchName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- List Branches -->
                    <ul class="space-y-2">
                        @foreach($branches as $branch)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingBranchId == $branch->id)
                                    <form wire:submit.prevent="updateBranch" class="flex flex-1">
                                        <input type="text" wire:model="editingBranchName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelBranch" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <span>{{ $branch->name }}</span>
                                    <div>
                                        <button wire:click="editBranch({{ $branch->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="deleteBranch({{ $branch->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Departments Tab -->
            <div x-show="activeTab === 'departments'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Departments</h2>

                    <!-- Add New Department -->
                    <form wire:submit.prevent="createDepartment" class="mb-4 space-y-2">
                        <div class="flex space-x-2">
                            <input type="text" wire:model="newDepartmentName" placeholder="Department Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <select wire:model="newDepartmentLocationId" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('newDepartmentName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @error('newDepartmentLocationId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- List Departments -->
                    <ul class="space-y-2">
                        @foreach($departments as $department)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingDepartmentId == $department->id)
                                    <form wire:submit.prevent="updateDepartment" class="flex flex-1 space-x-2">
                                        <input type="text" wire:model="editingDepartmentName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <select wire:model="editingDepartmentLocationId" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <option value="">Select Location</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}" {{ $location->id == $editingDepartmentLocationId ? 'selected' : '' }}>{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelDepartment" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <div>
                                        <span class="font-medium">{{ $department->name }}</span>
                                        <p class="text-sm text-gray-600">Location: {{ $department->location->name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <button wire:click="editDepartment({{ $department->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="deleteDepartment({{ $department->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Due Times Tab -->
            <div x-show="activeTab === 'dueTimes'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Todo Due Times</h2>

                    <!-- Add New Due Time -->
                    <form wire:submit.prevent="createDueTime" class="mb-4 space-y-2">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                            <select wire:model="newDueTimeCategoryId" class="border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model="newDueTimePriorityId" class="border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Select Priority</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority->id }}">{{ $priority->level }}</option>
                                @endforeach
                            </select>
                            <input type="number" wire:model="newDueTimeDuration" placeholder="Duration (hours)" min="1" class="border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <textarea wire:model="newDueTimeDescription" placeholder="Description" rows="1" class="border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Add Due Time</button>
                        @error('newDueTimeCategoryId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @error('newDueTimePriorityId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @error('newDueTimeDuration') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @error('newDueTimeDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- List Due Times -->
                    <ul class="space-y-2">
                        @foreach($dueTimes as $dueTime)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingDueTimeId == $dueTime->id)
                                    <form wire:submit.prevent="updateDueTime" class="flex flex-1 space-x-2">
                                        <select wire:model="editingDueTimeCategoryId" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ $category->id == $editingDueTimeCategoryId ? 'selected' : '' }}>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        <select wire:model="editingDueTimePriorityId" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <option value="">Select Priority</option>
                                            @foreach($priorities as $priority)
                                                <option value="{{ $priority->id }}" {{ $priority->id == $editingDueTimePriorityId ? 'selected' : '' }}>{{ $priority->level }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" wire:model="editingDueTimeDuration" min="1" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <textarea wire:model="editingDueTimeDescription" rows="1" class="flex-1 border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelDueTime" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <div>
                                        <span class="font-medium">{{ $dueTime->category->name ?? 'N/A' }} - {{ $dueTime->priority->level ?? 'N/A' }}</span>
                                        <p class="text-sm text-gray-600">Duration: {{ $dueTime->duration }} hours</p>
                                        @if($dueTime->description)
                                            <p class="text-sm text-gray-600">{{ $dueTime->description }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <button wire:click="editDueTime({{ $dueTime->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="deleteDueTime({{ $dueTime->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

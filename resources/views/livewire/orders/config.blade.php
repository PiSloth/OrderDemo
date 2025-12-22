<div>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold mb-6">Order Configuration</h1>

        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif

        <div x-data="{ activeTab: 'users', showDeleteConfirm: false, deleteItemId: null, deleteType: '' }" class="space-y-6">
            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 overflow-x-auto">
                    <button @click="activeTab = 'users'" :class="activeTab === 'users' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Users
                    </button>
                    <button @click="activeTab = 'positions'" :class="activeTab === 'positions' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Positions
                    </button>
                    <button @click="activeTab = 'categories'" :class="activeTab === 'categories' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Categories
                    </button>
                    <button @click="activeTab = 'statuses'" :class="activeTab === 'statuses' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Statuses
                    </button>
                    <button @click="activeTab = 'designs'" :class="activeTab === 'designs' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Designs
                    </button>
                    <button @click="activeTab = 'qualities'" :class="activeTab === 'qualities' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Qualities
                    </button>
                    <button @click="activeTab = 'branches'" :class="activeTab === 'branches' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Branches
                    </button>
                    <button @click="activeTab = 'grades'" :class="activeTab === 'grades' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Grades
                    </button>
                    <button @click="activeTab = 'priorities'" :class="activeTab === 'priorities' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                        Priorities
                    </button>
                </nav>
            </div>

            <!-- Users Tab -->
            <div x-show="activeTab === 'users'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">
                            Users Management
                            <span class="text-sm text-gray-500 ml-2">
                                ({{ $showSuspendedUsers ? 'Suspended Users' : 'Active Users' }})
                            </span>
                        </h2>
                        <div class="flex space-x-2">
                            <button wire:click="toggleSuspendedView"
                                    class="px-4 py-2 {{ $showSuspendedUsers ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition-colors">
                                <i class="fas {{ $showSuspendedUsers ? 'fa-user-check' : 'fa-user-times' }} mr-2"></i>
                                {{ $showSuspendedUsers ? 'Show Active Users' : 'Show Suspended Users' }}
                            </button>
                            <button wire:click="$set('showCreateUserModal', true)"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Create User
                            </button>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($users as $user)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->position?->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->department?->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->location?->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->branch?->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button wire:click="openEditUserModal({{ $user->id }})"
                                                        class="text-indigo-600 hover:text-indigo-900 p-1 rounded">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button wire:click="openChangePasswordModal({{ $user->id }})"
                                                        class="text-yellow-600 hover:text-yellow-900 p-1 rounded">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <button wire:click="suspendUser({{ $user->id }})"
                                                        class="text-orange-600 hover:text-orange-900 p-1 rounded">
                                                    <i class="fas {{ $user->suspended ? 'fa-user-check' : 'fa-ban' }}"></i>
                                                </button>
                                                <button @click="if(confirm('Are you sure you want to delete this user? This action cannot be undone.')) { $wire.delete_user({{ $user->id }}) }"
                                                        class="text-red-600 hover:text-red-900 p-1 rounded">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">No users found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Positions Tab -->
            <div x-show="activeTab === 'positions'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Positions</h2>

                    <!-- Add New Position -->
                    <form wire:submit.prevent="create_position" class="mb-4">
                        <div class="flex space-x-2">
                            <input type="text" wire:model="position" placeholder="Position Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('position') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- Positions List -->
                    <ul class="space-y-2">
                        @foreach($positions as $pos)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingPositionId == $pos->id)
                                    <form wire:submit.prevent="updatePosition" class="flex flex-1 space-x-2">
                                        <input type="text" wire:model="editingPositionName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelEditPosition" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <span>{{ $pos->name }}</span>
                                    <div>
                                        <button wire:click="editPosition({{ $pos->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="confirmDeletePosition({{ $pos->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Categories Tab -->
            <div x-show="activeTab === 'categories'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Categories</h2>

                    <!-- Add New Category -->
                    <form wire:submit.prevent="create_category" class="mb-4">
                        <div class="flex space-x-2">
                            <input type="text" wire:model="category" placeholder="Category Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('category') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- Categories List -->
                    <ul class="space-y-2">
                        @foreach($categories as $cat)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingCategoryId == $cat->id)
                                    <form wire:submit.prevent="updateCategory" class="flex flex-1 space-x-2">
                                        <input type="text" wire:model="editingCategoryName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelEditCategory" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <span>{{ $cat->name }}</span>
                                    <div>
                                        <button wire:click="editCategory({{ $cat->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="confirmDeleteCategory({{ $cat->id }})" class="text-red-600 hover:text-red-900">Delete</button>
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
                    <h2 class="text-lg font-medium mb-4">Statuses</h2>

                    <!-- Add New Status -->
                    <form wire:submit.prevent="create_status" class="mb-4">
                        <div class="flex space-x-2">
                            <input type="text" wire:model="status" placeholder="Status Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- Statuses List -->
                    <ul class="space-y-2">
                        @foreach($statuses as $stat)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingStatusId == $stat->id)
                                    <form wire:submit.prevent="updateStatus" class="flex flex-1 space-x-2">
                                        <input type="text" wire:model="editingStatusName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelEditStatus" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <span>{{ $stat->name }}</span>
                                    <div>
                                        <button wire:click="editStatus({{ $stat->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="confirmDeleteStatus({{ $stat->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Designs Tab -->
            <div x-show="activeTab === 'designs'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Designs</h2>

                    <!-- Add New Design -->
                    <form wire:submit.prevent="create_design" class="mb-4">
                        <div class="flex space-x-2">
                            <input type="text" wire:model="design" placeholder="Design Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('design') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- Designs List -->
                    <ul class="space-y-2">
                        @foreach($designs as $des)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingDesignId == $des->id)
                                    <form wire:submit.prevent="updateDesign" class="flex flex-1 space-x-2">
                                        <input type="text" wire:model="editingDesignName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelEditDesign" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <span>{{ $des->name }}</span>
                                    <div>
                                        <button wire:click="editDesign({{ $des->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="confirmDeleteDesign({{ $des->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Qualities Tab -->
            <div x-show="activeTab === 'qualities'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Qualities</h2>

                    <!-- Add New Quality -->
                    <form wire:submit.prevent="create_quality" class="mb-4">
                        <div class="flex space-x-2">
                            <input type="text" wire:model="quality" placeholder="Quality Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('quality') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- Qualities List -->
                    <ul class="space-y-2">
                        @foreach($qualities as $qual)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingQualityId == $qual->id)
                                    <form wire:submit.prevent="updateQuality" class="flex flex-1 space-x-2">
                                        <input type="text" wire:model="editingQualityName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelEditQuality" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <span>{{ $qual->name }}</span>
                                    <div>
                                        <button wire:click="editQuality({{ $qual->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="confirmDeleteQuality({{ $qual->id }})" class="text-red-600 hover:text-red-900">Delete</button>
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
                    <form wire:submit.prevent="create_branch" class="mb-4">
                        <div class="flex space-x-2 items-center">
                            <input type="text" wire:model="branch" placeholder="Branch Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="is_jewelry_shop" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Jewelry Shop</span>
                            </label>
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('branch') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- Branches List -->
                    <ul class="space-y-2">
                        @foreach($branches as $br)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingBranchId == $br->id)
                                    <form wire:submit.prevent="updateBranch" class="flex flex-1 space-x-2 items-center">
                                        <input type="text" wire:model="editingBranchName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="editingBranchIsJewelryShop" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-gray-700">Jewelry Shop</span>
                                        </label>
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelEditBranch" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <span>{{ $br->name }} @if($br->is_jewelry_shop) <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Jewelry Shop</span> @endif</span>
                                    <div>
                                        <button wire:click="editBranch({{ $br->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="confirmDeleteBranch({{ $br->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Grades Tab -->
            <div x-show="activeTab === 'grades'" class="space-y-4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Grades</h2>

                    <!-- Add New Grade -->
                    <form wire:submit.prevent="create_grade" class="mb-4">
                        <div class="flex space-x-2">
                            <input type="text" wire:model="grade" placeholder="Grade Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('grade') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- Grades List -->
                    <ul class="space-y-2">
                        @foreach($grades as $gr)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingGradeId == $gr->id)
                                    <form wire:submit.prevent="updateGrade" class="flex flex-1 space-x-2">
                                        <input type="text" wire:model="editingGradeName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelEditGrade" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <span>{{ $gr->name }}</span>
                                    <div>
                                        <button wire:click="editGrade({{ $gr->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="confirmDeleteGrade({{ $gr->id }})" class="text-red-600 hover:text-red-900">Delete</button>
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
                    <h2 class="text-lg font-medium mb-4">Priorities</h2>

                    <!-- Add New Priority -->
                    <form wire:submit.prevent="create_priority" class="mb-4">
                        <div class="flex space-x-3">
                            <input type="text" wire:model="priority" placeholder="Priority Name" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <input type="color" wire:model="color" class="w-16 h-10 border border-gray-300 rounded">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-md hover:bg-indigo-700">Add</button>
                        </div>
                        @error('priority') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @error('color') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </form>

                    <!-- Priorities List -->
                    <ul class="space-y-2">
                        @foreach($priorities as $pri)
                            <li class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                @if($editingPriorityId == $pri->id)
                                    <form wire:submit.prevent="updatePriority" class="flex flex-1 space-x-3">
                                        <input type="text" wire:model="editingPriorityName" class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <input type="color" wire:model="editingPriorityColor" class="w-16 h-10 border border-gray-300 rounded">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-r-md hover:bg-green-700 mr-2">Save</button>
                                        <button type="button" wire:click="cancelEditPriority" class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700">Cancel</button>
                                    </form>
                                @else
                                    <div class="flex items-center space-x-2">
                                        <span>{{ $pri->name }}</span>
                                        <div class="w-4 h-4 rounded" style="background-color: {{ $pri->color }}"></div>
                                    </div>
                                    <div>
                                        <button wire:click="editPriority({{ $pri->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                                        <button wire:click="confirmDeletePriority({{ $pri->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if($showCreateUserModal || $showEditUserModal || $showChangePasswordModal)
        <style>
            body { overflow: hidden; }
        </style>
    @endif

    {{-- Create User Modal --}}
    @if($showCreateUserModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="create-user-modal">
        <div class="relative top-20 mx-auto p-5 border max-w-2xl w-full shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create New User</h3>
                    <button wire:click="closeModals" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form wire:submit="create_user" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" wire:model="username" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                        @error('username') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" wire:model="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                        @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" wire:model="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                        @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Position</label>
                        <select wire:model="position_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Select Position</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                        @error('position_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Branch</label>
                        <select wire:model="branch_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Select Branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Department</label>
                        <select wire:model="department_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Select Department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Location</label>
                        <select wire:model="location_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Select Location</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                        @error('location_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="closeModals" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Edit User Modal --}}
    @if($showEditUserModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="edit-user-modal">
        <div class="relative top-20 mx-auto p-5 border max-w-2xl w-full shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit User</h3>
                    <button wire:click="closeModals" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form wire:submit="updateUser" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" wire:model="editUsername" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                        @error('editUsername') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" wire:model="editEmail" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                        @error('editEmail') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Position</label>
                        <select wire:model="editPositionId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Select Position</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                        @error('editPositionId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Branch</label>
                        <select wire:model="editBranchId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Select Branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('editBranchId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Department</label>
                        <select wire:model="editDepartmentId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Select Department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        @error('editDepartmentId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Location</label>
                        <select wire:model="editLocationId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Select Location</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                        @error('editLocationId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="closeModals" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Change Password Modal --}}
    @if($showChangePasswordModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="change-password-modal">
        <div class="relative top-20 mx-auto p-5 border max-w-md w-full shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Change Password</h3>
                    <button wire:click="closeModals" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form wire:submit="changePassword" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" wire:model="newPassword" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                        @error('newPassword') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" wire:model="confirmPassword" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                        @error('confirmPassword') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="closeModals" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('livewire:loaded', () => {
    Livewire.on('confirm-delete', (data) => {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            $wire.delete_user(data.userId);
        }
    });

    Livewire.on('confirm-delete-position', (data) => {
        if (confirm('Are you sure you want to delete this position?')) {
            $wire.delete_position(data.positionId);
        }
    });

    Livewire.on('confirm-delete-category', (data) => {
        if (confirm('Are you sure you want to delete this category?')) {
            $wire.delete_category(data.categoryId);
        }
    });

    Livewire.on('confirm-delete-status', (data) => {
        if (confirm('Are you sure you want to delete this status?')) {
            $wire.delete_status(data.statusId);
        }
    });

    Livewire.on('confirm-delete-design', (data) => {
        if (confirm('Are you sure you want to delete this design?')) {
            $wire.delete_design(data.designId);
        }
    });

    Livewire.on('confirm-delete-quality', (data) => {
        if (confirm('Are you sure you want to delete this quality?')) {
            $wire.delete_quality(data.qualityId);
        }
    });

    Livewire.on('confirm-delete-branch', (data) => {
        if (confirm('Are you sure you want to delete this branch?')) {
            $wire.delete_branch(data.branchId);
        }
    });

    Livewire.on('confirm-delete-grade', (data) => {
        if (confirm('Are you sure you want to delete this grade?')) {
            $wire.delete_grade(data.gradeId);
        }
    });

    Livewire.on('confirm-delete-priority', (data) => {
        if (confirm('Are you sure you want to delete this priority?')) {
            $wire.delete_priority(data.priorityId);
        }
    });

    Livewire.on('show-error', (data) => {
        alert(data.message);
    });
});
</script>

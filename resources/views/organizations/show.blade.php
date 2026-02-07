@extends('layouts.app')

@section('title', $organization->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="organizationShowApp()">
    <!-- Header con Breadcrumbs -->
    <div class="mb-6">
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li>
                    <a href="{{ route('organizations.index') }}" class="text-gray-600 hover:text-purple-600">
                        Organizaciones
                    </a>
                </li>
                <li>
                    <span class="mx-2 text-gray-400">/</span>
                </li>
                <li class="text-gray-900 font-medium">{{ $organization->name }}</li>
            </ol>
        </nav>

        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold text-gray-900">{{ $organization->name }}</h1>
                    @if($organization->isOwner(auth()->user()))
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-full">
                            👑 Propietario
                        </span>
                    @elseif($userRole === 'admin')
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                            🛡️ Admin
                        </span>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">
                            👤 Miembro
                        </span>
                    @endif
                </div>
                @if($organization->description)
                    <p class="text-gray-600">{{ $organization->description }}</p>
                @endif
            </div>
            @can('update', $organization)
                <button @click="showEditModal = true"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Editar
                </button>
            @endcan
        </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Miembros</p>
            <p class="text-2xl font-bold text-purple-600">{{ $members->count() }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Emails</p>
            <p class="text-2xl font-bold text-blue-600">{{ $organization->emails()->count() }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Carpetas Compartidas</p>
            <p class="text-2xl font-bold text-green-600">{{ $organization->emailFolders()->whereNotNull('organization_id')->count() }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Templates Compartidos</p>
            <p class="text-2xl font-bold text-orange-600">{{ $organization->emailTemplates()->whereNotNull('organization_id')->count() }}</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'members'"
                        :class="activeTab === 'members' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Miembros
                </button>
                <button @click="activeTab = 'invitations'"
                        :class="activeTab === 'invitations' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Invitaciones
                </button>
                @can('viewMembers', $organization)
                <button @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Historial
                    @if($formerMembers->count() > 0)
                        <span class="ml-2 bg-gray-100 text-gray-600 py-1 px-2 rounded-full text-xs">
                            {{ $formerMembers->count() }}
                        </span>
                    @endif
                </button>
                @endcan
                <button @click="activeTab = 'settings'"
                        :class="activeTab === 'settings' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Configuración
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Content: Miembros -->
    <div x-show="activeTab === 'members'">
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Miembros ({{ $members->count() }})</h2>
                @can('inviteMembers', $organization)
                    <button @click="showInviteModal = true"
                            class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Invitar Miembro
                    </button>
                @endcan
            </div>

            <div class="divide-y divide-gray-200">
                @foreach($members as $member)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-semibold text-lg">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-gray-900">{{ $member->name }}</p>
                                    @if($organization->isOwner($member))
                                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">👑 Owner</span>
                                    @elseif($member->pivot->role === 'admin')
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">🛡️ Admin</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-800 text-xs font-medium rounded-full">👤 Member</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500">{{ $member->email }}</p>
                                <p class="text-xs text-gray-400">Se unió {{ \Carbon\Carbon::parse($member->pivot->joined_at)->diffForHumans() }}</p>
                            </div>
                        </div>

                        @can('removeMembers', $organization)
                            @if(!$organization->isOwner($member))
                                <div class="flex items-center gap-2">
                                    @can('changeMemberRoles', $organization)
                                        <select @change="changeMemberRole({{ $member->id }}, $event.target.value)"
                                                class="text-sm text-gray-900 bg-white border-gray-300 rounded-md focus:border-purple-500 focus:ring-purple-500">
                                            <option value="admin" {{ $member->pivot->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                            <option value="member" {{ $member->pivot->role === 'member' ? 'selected' : '' }}>Member</option>
                                        </select>
                                    @endcan

                                    <button @click="confirmRemoveMember({{ $member->id }}, '{{ $member->name }}')"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Remover miembro">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        @endcan
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Tab Content: Invitaciones -->
    <div x-show="activeTab === 'invitations'">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <p class="text-gray-600">Sistema de invitaciones próximamente...</p>
        </div>
    </div>

    <!-- Tab Content: Historial de Miembros -->
    @can('viewMembers', $organization)
    <div x-show="activeTab === 'history'">
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Historial de Miembros ({{ $formerMembers->count() }})</h2>
                <p class="text-sm text-gray-600 mt-1">Usuarios que han dejado la organización</p>
            </div>

            @if($formerMembers->isEmpty())
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">No hay miembros en el historial</p>
                </div>
            @else
                <div class="divide-y divide-gray-200">
                    @foreach($formerMembers as $member)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <!-- Info del usuario -->
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-semibold text-lg">
                                        {{ substr($member->pivot->user_name ?? 'U', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ $member->pivot->user_name ?? 'Usuario eliminado' }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            {{ $member->pivot->user_email ?? 'Email no disponible' }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                                                {{ ucfirst($member->pivot->role) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Info de salida -->
                                <div class="text-right text-sm">
                                    <p class="text-gray-600">
                                        <span class="font-medium">Salió:</span>
                                        {{ $member->pivot->left_at ? \Carbon\Carbon::parse($member->pivot->left_at)->format('d/m/Y H:i') : 'N/A' }}
                                    </p>
                                    @if($member->pivot->removed_by)
                                        @php
                                            $remover = \App\Models\User::find($member->pivot->removed_by);
                                        @endphp
                                        <p class="text-gray-600 mt-1">
                                            <span class="font-medium">Removido por:</span>
                                            {{ $remover ? $remover->name : 'Usuario #' . $member->pivot->removed_by }}
                                        </p>
                                    @endif
                                    @if($member->pivot->removal_reason)
                                        <p class="text-gray-600 mt-1 max-w-xs">
                                            <span class="font-medium">Razón:</span>
                                            <span class="italic">{{ $member->pivot->removal_reason }}</span>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endcan

    <!-- Tab Content: Configuración -->
    <div x-show="activeTab === 'settings'">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Información de la Organización</h3>

            <dl class="space-y-3">
                @if($organization->email)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $organization->email }}</dd>
                    </div>
                @endif
                @if($organization->website)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Sitio Web</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ $organization->website }}" target="_blank" class="text-purple-600 hover:text-purple-700">
                                {{ $organization->website }}
                            </a>
                        </dd>
                    </div>
                @endif
                @if($organization->phone)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $organization->phone }}</dd>
                    </div>
                @endif
                @if($organization->address)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Dirección</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $organization->address }}</dd>
                    </div>
                @endif
            </dl>

            @can('leave', $organization)
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-orange-600 mb-2">Salir de la Organización</h4>
                    <p class="text-sm text-gray-600 mb-4">
                        Si sales de esta organización, perderás acceso a todos los recursos compartidos.
                    </p>
                    <button @click="confirmLeave()"
                            class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors">
                        Salir de la Organización
                    </button>
                </div>
            @endcan

            @can('delete', $organization)
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-red-600 mb-2">Zona de Peligro</h4>
                    <p class="text-sm text-gray-600 mb-4">
                        Eliminar la organización es permanente y no se puede deshacer.
                    </p>
                    <button @click="confirmDelete()"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                        Eliminar Organización
                    </button>
                </div>
            @endcan
        </div>
    </div>

    <!-- Modal Invitar Miembro -->
    <div x-show="showInviteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showInviteModal = false"></div>

            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full">
                <form @submit.prevent="inviteMember()">
                    <div class="px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Invitar Miembro</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email *</label>
                                <input type="email"
                                       x-model="invitation.email"
                                       required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-gray-900">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Rol *</label>
                                <select x-model="invitation.role"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-gray-900">
                                    <option value="member">👤 Member (Usuario normal)</option>
                                    <option value="admin">🛡️ Admin (Co-administrador)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    Los Admins pueden invitar usuarios y gestionar recursos compartidos.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-2">
                        <button type="button"
                                @click="showInviteModal = false"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                :disabled="inviting"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md disabled:bg-purple-400">
                            <span x-show="!inviting">Enviar Invitación</span>
                            <span x-show="inviting">Enviando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function organizationShowApp() {
    return {
        activeTab: 'members',
        showInviteModal: false,
        showEditModal: false,
        inviting: false,
        invitation: {
            email: '',
            role: 'member'
        },

        async inviteMember() {
            this.inviting = true;

            try {
                const response = await fetch('/organizations/{{ $organization->id }}/invitations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.invitation)
                });

                const data = await response.json();

                if (response.ok) {
                    // Mostrar el mensaje tal cual viene del servidor (ya incluye el ícono)
                    alert(data.message);
                    this.showInviteModal = false;
                    this.invitation = { email: '', role: 'member' };
                } else {
                    alert('❌ ' + (data.message || 'Error al enviar la invitación'));
                }
            } catch (error) {
                alert('❌ Error al enviar la invitación');
                console.error(error);
            } finally {
                this.inviting = false;
            }
        },

        async changeMemberRole(userId, newRole) {
            try {
                const response = await fetch(`/organizations/{{ $organization->id }}/members/${userId}/role`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ role: newRole })
                });

                const data = await response.json();

                if (response.ok) {
                    alert('✅ ' + data.message);
                    window.location.reload();
                } else {
                    alert('❌ ' + (data.message || 'Error al cambiar el rol'));
                }
            } catch (error) {
                alert('❌ Error al cambiar el rol');
                console.error(error);
            }
        },

        confirmRemoveMember(userId, userName) {
            if (confirm(`¿Estás seguro de remover a ${userName} de la organización?`)) {
                this.removeMember(userId);
            }
        },

        async removeMember(userId) {
            try {
                const response = await fetch(`/organizations/{{ $organization->id }}/members/${userId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    alert('✅ ' + data.message);
                    window.location.reload();
                } else {
                    alert('❌ ' + (data.message || 'Error al remover el miembro'));
                }
            } catch (error) {
                alert('❌ Error al remover el miembro');
                console.error(error);
            }
        },

        confirmLeave() {
            if (confirm('⚠️ ¿Estás seguro de salir de esta organización?\n\nPerderás acceso a todos los recursos compartidos.')) {
                this.leaveOrganization();
            }
        },

        async leaveOrganization() {
            try {
                const response = await fetch('/organizations/{{ $organization->id }}/leave', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    alert('✅ ' + data.message);
                    window.location.href = '/organizations';
                } else {
                    alert('❌ ' + (data.message || 'Error al salir de la organización'));
                }
            } catch (error) {
                alert('❌ Error al salir de la organización');
                console.error(error);
            }
        },

        confirmDelete() {
            if (confirm('⚠️ ¿Estás SEGURO de eliminar esta organización?\n\nEsta acción NO se puede deshacer.')) {
                this.deleteOrganization();
            }
        },

        async deleteOrganization() {
            try {
                const response = await fetch('/organizations/{{ $organization->id }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    window.location.href = '/organizations';
                } else {
                    const data = await response.json();
                    alert('❌ ' + (data.message || 'Error al eliminar la organización'));
                }
            } catch (error) {
                alert('❌ Error al eliminar la organización');
                console.error(error);
            }
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection

import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Mail, CheckCircle, XCircle, Clock, RefreshCw, Trash2, Play, Pause } from 'lucide-react';

export default function Index({ accounts, flash }) {
    const [processingId, setProcessingId] = useState(null);

    const handleToggleSync = async (accountId) => {
        setProcessingId(accountId);
        try {
            const response = await fetch(`/email-accounts/${accountId}/toggle-sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (response.ok) {
                router.reload({ only: ['accounts'] });
            }
        } catch (error) {
            console.error('Error toggling sync:', error);
        } finally {
            setProcessingId(null);
        }
    };

    const handleSyncNow = async (accountId) => {
        setProcessingId(accountId);
        try {
            const response = await fetch(`/email-accounts/${accountId}/sync-now`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            const data = await response.json();
            if (data.success) {
                // Mostrar mensaje de sincronización iniciada
                const notification = document.createElement('div');
                notification.className = 'fixed bottom-4 right-4 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 z-50';
                notification.innerHTML = `
                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    <span>Sincronizando emails... Esto puede tardar unos minutos</span>
                `;
                document.body.appendChild(notification);

                // Auto-remover después de 5 segundos
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }
        } catch (error) {
            console.error('Error syncing:', error);
            alert('Error al iniciar la sincronización');
        } finally {
            setProcessingId(null);
        }
    };

    const handleDelete = async (accountId, email) => {
        if (!accountId) {
            alert('Error: No se puede desconectar la cuenta. ID no disponible.');
            return;
        }

        if (!confirm(`¿Estás seguro de desconectar la cuenta ${email}?\nSe eliminarán todos los emails asociados a esta cuenta.`)) {
            return;
        }

        setProcessingId(accountId);
        try {
            const response = await fetch(`/email-accounts/${accountId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                window.location.href = '/email-accounts';
            } else {
                const data = await response.json();
                alert('Error al desconectar la cuenta: ' + (data.message || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('Error al desconectar la cuenta');
        } finally {
            setProcessingId(null);
        }
    };

    const getProviderIcon = (provider) => {
        if (provider === 'gmail') {
            return (
                <div className="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <svg className="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L12 9.545l8.073-6.052C21.69 2.28 24 3.434 24 5.457z"/>
                    </svg>
                </div>
            );
        }
        return (
            <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <svg className="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7.88 12.04q0 .45-.11.87-.1.41-.33.74-.22.33-.58.52-.37.2-.87.2t-.85-.2q-.35-.21-.57-.55-.22-.33-.33-.75-.1-.42-.1-.86t.1-.87q.1-.43.34-.76.22-.34.59-.54.36-.2.87-.2t.86.2q.35.21.57.55.22.34.31.77.1.43.1.88zM24 12v9.38q0 .46-.33.8-.33.32-.8.32H7.13q-.46 0-.8-.33-.32-.33-.32-.8V18H1q-.41 0-.7-.3-.3-.29-.3-.7V7q0-.41.3-.7Q.58 6 1 6h6.5V2.2q0-.44.32-.77.34-.33.8-.33h12.87q.47 0 .8.33.33.33.33.77V12z"/>
                </svg>
            </div>
        );
    };

    const getStatusBadge = (status) => {
        if (status === 'active') {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <span className="w-1.5 h-1.5 bg-green-400 rounded-full mr-1"></span>
                    Activa
                </span>
            );
        }
        if (status === 'error') {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    Error
                </span>
            );
        }
        return (
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                Pausada
            </span>
        );
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'Nunca';
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'Hace un momento';
        if (diff < 3600) return `Hace ${Math.floor(diff / 60)} minutos`;
        if (diff < 86400) return `Hace ${Math.floor(diff / 3600)} horas`;
        return `Hace ${Math.floor(diff / 86400)} días`;
    };

    return (
        <>
            <Head title="Cuentas de Email" />

            <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <header className="bg-white border-b border-gray-200">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <div className="flex justify-between items-center">
                            <div>
                                <h1 className="text-2xl font-semibold text-gray-900">Cuentas de Email</h1>
                                <p className="text-sm text-gray-600 mt-1">Gestiona tus cuentas conectadas de Gmail y Outlook</p>
                            </div>
                            <a href="/emails" className="text-sm text-gray-600 hover:text-gray-900">
                                ← Volver a Emails
                            </a>
                        </div>
                    </div>
                </header>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Flash Messages */}
                    {flash?.success && (
                        <div className="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                            {flash.success}
                        </div>
                    )}

                    {flash?.error && (
                        <div className="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                            {flash.error}
                        </div>
                    )}

                    {/* Connect New Account */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6 mb-6">
                        <h2 className="text-lg font-medium text-gray-900 mb-4">Conectar Nueva Cuenta</h2>
                        <div className="flex gap-4">
                            <a
                                href="/email-accounts/google/redirect"
                                className="inline-flex items-center px-4 py-2 bg-white text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                            >
                                <svg className="w-5 h-5 mr-2" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                Conectar Gmail
                            </a>

                            <a
                                href="/email-accounts/microsoft/redirect"
                                className="inline-flex items-center px-4 py-2 bg-white text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                            >
                                <svg className="w-5 h-5 mr-2" viewBox="0 0 24 24">
                                    <path fill="#f25022" d="M0 0h11v11H0z"/>
                                    <path fill="#00a4ef" d="M13 0h11v11H13z"/>
                                    <path fill="#7fba00" d="M0 13h11v11H0z"/>
                                    <path fill="#ffb900" d="M13 13h11v11H13z"/>
                                </svg>
                                Conectar Outlook
                            </a>
                        </div>
                    </div>

                    {/* Connected Accounts */}
                    <div className="bg-white rounded-lg border border-gray-200">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h2 className="text-lg font-medium text-gray-900">Cuentas Conectadas</h2>
                        </div>

                        {accounts.length === 0 ? (
                            <div className="px-6 py-12 text-center">
                                <Mail className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No hay cuentas conectadas</h3>
                                <p className="mt-1 text-sm text-gray-500">Comienza conectando tu primera cuenta de Gmail o Outlook</p>
                            </div>
                        ) : (
                            <div className="divide-y divide-gray-200">
                                {accounts.map((account) => (
                                    <div key={account.id} className="px-6 py-4">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center flex-1">
                                                {getProviderIcon(account.provider)}

                                                <div className="ml-4 flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <h3 className="text-sm font-medium text-gray-900">{account.email_address}</h3>
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                            {account.provider_name}
                                                        </span>
                                                        {getStatusBadge(account.sync_status)}
                                                    </div>
                                                    {account.display_name && (
                                                        <p className="text-sm text-gray-500">{account.display_name}</p>
                                                    )}
                                                    <div className="mt-1 text-xs text-gray-500">
                                                        {account.last_sync_at
                                                            ? `Última sincronización: ${formatDate(account.last_sync_at)}`
                                                            : 'Sincronización pendiente'
                                                        }
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Actions */}
                                            <div className="ml-4 flex items-center gap-2">
                                                <button
                                                    onClick={() => handleToggleSync(account.id)}
                                                    disabled={processingId === account.id}
                                                    className={`inline-flex items-center px-3 py-1.5 text-sm rounded-md transition ${
                                                        account.sync_enabled
                                                            ? 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100'
                                                            : 'bg-green-50 text-green-700 hover:bg-green-100'
                                                    } disabled:opacity-50`}
                                                >
                                                    {account.sync_enabled ? <Pause className="w-4 h-4 mr-1" /> : <Play className="w-4 h-4 mr-1" />}
                                                    {account.sync_enabled ? 'Pausar' : 'Activar'}
                                                </button>

                                                <button
                                                    onClick={() => handleSyncNow(account.id)}
                                                    disabled={processingId === account.id}
                                                    className="inline-flex items-center px-3 py-1.5 text-sm bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-md transition disabled:opacity-50"
                                                >
                                                    <RefreshCw className={`w-4 h-4 mr-1 ${processingId === account.id ? 'animate-spin' : ''}`} />
                                                    {processingId === account.id ? 'Sincronizando...' : 'Sincronizar'}
                                                </button>

                                                <button
                                                    onClick={() => handleDelete(account.id, account.email_address)}
                                                    className="inline-flex items-center px-3 py-1.5 text-sm bg-red-50 text-red-700 hover:bg-red-100 rounded-md transition"
                                                >
                                                    <Trash2 className="w-4 h-4 mr-1" />
                                                    Desconectar
                                                </button>
                                            </div>
                                        </div>

                                        {/* Sync Logs */}
                                        {account.sync_logs && account.sync_logs.length > 0 && (
                                            <div className="mt-4 border-t border-gray-100 pt-4">
                                                <h4 className="text-xs font-medium text-gray-700 mb-2">Historial de Sincronización</h4>
                                                <div className="space-y-2">
                                                    {account.sync_logs.slice(0, 3).map((log, index) => (
                                                        <div key={index} className="flex items-center justify-between text-xs">
                                                            <div className="flex items-center gap-2">
                                                                {log.status === 'success' ? (
                                                                    <CheckCircle className="w-3 h-3 text-green-500" />
                                                                ) : (
                                                                    <XCircle className="w-3 h-3 text-red-500" />
                                                                )}
                                                                <span className="text-gray-600">
                                                                    {log.sync_type} - {new Date(log.started_at).toLocaleString('es-ES')}
                                                                </span>
                                                            </div>
                                                            <div className="text-gray-500">
                                                                <span className="text-green-600">+{log.emails_created}</span> /
                                                                <span className="text-blue-600"> ~{log.emails_updated}</span>
                                                                {log.emails_failed > 0 && (
                                                                    <span className="text-red-600"> !{log.emails_failed}</span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

<style>
    .pwa-user-panel {
        background: #f8fafc;
        height: 100%;
    }

    .pwa-user-panel .card {
        border-radius: 14px;
    }

    .pwa-user-panel .form-control {
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }

    .pwa-user-panel .form-control:focus {
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        border-color: #3b82f6;
    }

    .pwa-user-panel textarea {
        font-size: 0.85rem;
    }

    .panel-header {
        padding: 0 4px;
    }
</style>
<div class="pwa-user-panel p-3">

    {{-- Header --}}
    <div class="panel-header mb-3">
        <h6 class="fw-semibold mb-0">User Settings</h6>
        <small class="text-muted">Saved per device</small>
    </div>

    {{-- Form --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">

            {{-- Name --}}
            <div class="mb-3">
                <label class="form-label small text-muted">Name</label>
                <input type="text" wire:model="name" class="form-control form-control-lg">
            </div>

            {{-- Email --}}
            <div class="mb-3">
                <label class="form-label small text-muted">Email</label>
                <input type="email" wire:model="email" class="form-control form-control-lg">
            </div>

            {{-- Phone --}}
            <div class="mb-3">
                <label class="form-label small text-muted">Phone</label>
                <input type="text" wire:model="phone" class="form-control form-control-lg">
            </div>

            {{-- Divider --}}
            <hr class="my-4">

            {{-- Custom Settings --}}
            <div class="mb-3">
                <label class="form-label small text-muted">Custom Settings (JSON)</label>
                <textarea 
                    wire:model="custom_settings_json"
                    class="form-control font-monospace"
                    rows="5"
                ></textarea>
                <div class="form-text">
                    Key/value pairs for project-specific settings
                </div>
            </div>

            {{-- Save Button --}}
            <button wire:click="save" class="btn btn-primary w-100 py-2 mt-2">
                Save Preferences
            </button>

        </div>
    </div>
</div>
@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }
.rf-label { font-size:11px; font-weight:700; color:#64748b; letter-spacing:0.6px; text-transform:uppercase; margin-bottom:6px; display:block; }
.rf-input { height:42px; width:100%; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; background:#f8fafc; padding:0 14px; outline:none; color:#1e293b; transition:all 0.2s; box-sizing:border-box; }
.rf-input:focus { border-color:rgb(234, 88, 12); background:#fff; box-shadow:none; }
.form-btn-cancel {
    display: inline-flex; align-items: center; gap: 7px;
    height: 42px; padding: 0 22px; border-radius: 10px;
    border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b;
    font-weight: 600; font-size: 14px; text-decoration: none;
    transition: background 0.18s, border-color 0.18s, color 0.18s;
    cursor: pointer;
}
.form-btn-cancel:hover, .form-btn-cancel:active { background: #fff8f3; border-color: rgb(234, 88, 12); color: rgb(234, 88, 12); text-decoration: none; }
.form-btn-save {
    display: inline-flex; align-items: center; gap: 7px;
    height: 42px; padding: 0 26px; border-radius: 10px;
    background: rgb(234, 88, 12); color: #fff;
    font-weight: 700; font-size: 14px; border: none;
    box-shadow: 0 4px 14px rgba(234,88,12,0.35); cursor: pointer;
    transition: box-shadow 0.18s, transform 0.18s; outline: none;
}
.form-btn-save:hover { box-shadow: 0 6px 20px rgba(234,88,12,0.45); transform: translateY(-1px); }
.form-btn-save:active { transform: translateY(0); }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    var form = document.getElementById('updateRoleForm');
    if(!form) return;
    form.addEventListener('submit', function(e){
        e.preventDefault();
        document.querySelectorAll('[data-validate]').forEach(function(el){ el.textContent=''; });
        var btn = form.querySelector('[type=submit]');
        if(btn) btn.disabled = true;
        var fd = new FormData(form);
        fd.append('_method','PUT');
        fetch('{{ route("management.roles.role.edit.update", $data->id) }}', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if(btn) btn.disabled = false;
            if(res.success) {
                showRoleEditToast(res.payload.message || 'Role updated successfully.', 'success');
                setTimeout(function(){ window.location.href = '{{ route("management.settings.index") }}?tab=roles'; }, 1500);
            } else {
                showRoleEditToast(typeof res.payload === 'string' ? res.payload : 'Something went wrong.', 'error');
            }
        })
        .catch(function(){
            if(btn) btn.disabled = false;
            showRoleEditToast('An error occurred.', 'error');
        });
    });
});
function showRoleEditToast(msg, type) {
    var old = document.getElementById('roleEditToast');
    if(old) old.remove();
    var bg = type==='success' ? 'linear-gradient(135deg,#22c55e,#16a34a)' : 'linear-gradient(135deg,#ef4444,#dc2626)';
    var icon = type==='success' ? 'fa-check-circle' : 'fa-times-circle';
    var t = document.createElement('div');
    t.id = 'roleEditToast';
    t.style.cssText = 'position:fixed;top:24px;right:24px;z-index:99999;background:'+bg+';color:#fff;padding:14px 20px;border-radius:12px;font-size:14px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 6px 20px rgba(0,0,0,0.15);';
    t.innerHTML = '<i class="fa '+icon+'" style="font-size:16px;"></i><span>'+msg+'</span>';
    document.body.appendChild(t);
    setTimeout(function(){ if(t.parentNode) t.parentNode.removeChild(t); }, 3500);
}
</script>
@endpush

@section('content')
<section>

    {{-- Page heading --}}
    <div style="margin-bottom:20px;display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;border-radius:12px;background:rgb(234, 88, 12);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(234,88,12,0.3);">
            <i class="fa fa-shield" style="font-size:17px;color:#fff;"></i>
        </div>
        <div>
            <h1 style="font-size:20px;font-weight:800;color:#0f172a;margin:0 0 2px;">Edit <span style="color:rgb(234, 88, 12);">Role</span></h1>
            <p style="font-size:12px;color:#94a3b8;margin:0;">Update role name — #{{ $data->id }}</p>
        </div>
    </div>

    <div style="background:#fff;border-radius:16px;border:1px solid #e5e7eb;box-shadow:0 2px 10px rgba(0,0,0,0.05);padding:28px 32px;">
        <form id="updateRoleForm" name="updateRoleForm">
        @csrf
        @method('PUT')
            <div style="margin-bottom:24px;">
                <label class="rf-label">Role Name <span style="color:rgb(234, 88, 12);">*</span></label>
                <input type="text" class="rf-input" id="name" name="name" placeholder="Enter role name" value="{{ $data->name }}">
                <div data-validate="name" style="font-size:12px;color:#ef4444;margin-top:4px;"></div>
            </div>

            <div style="display:flex;justify-content:flex-end;align-items:center;gap:12px;padding-top:20px;border-top:1px solid #f1f5f9;">
                <a href="{{ route('management.settings.index') }}?tab=roles" class="form-btn-cancel">
                    <i class="fa fa-times" style="font-size:11px;"></i> Cancel
                </a>
                <button type="submit" class="form-btn-save">
                    <i class="fa fa-check"></i> Save Role
                </button>
            </div>
        </form>
    </div>

</section>
@endsection

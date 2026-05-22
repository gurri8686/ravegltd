@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection

<?php
$leftMenuData = (config('permissions'));
$module = module($leftMenuData);
function group($array,$permissionsExist,$childClass=null)
{
    $html1 = $valueCheckbox = $checked=$html1=$html2=$html3='';
    if(is_array($array))
    {
        $html1 .= '<ul class="perm-sub-ul">';
        foreach($array as $key=>$value)
        {
            $groupName=$checked='';
            if(is_numeric($key))
            {
                $groupName = group($value,$permissionsExist,$childClass);
            }else
            {
                $valueCheckbox=str_replace(' ','.',$childClass.' '.$key).'.*';
                $groupName=$key;
                $function = "selectall(this,'$groupName')";
                $html2 .= "<li class='perm-sub-item' module-id='".$key."'>
                    <label class='perm-check-label'>
                        <input class='parent".$key." ".$childClass."' type='checkbox' name='checkbox[]'
                            onClick=".$function." value='".$valueCheckbox."'
                            id='checkbox".$groupName."' ".isChecked($valueCheckbox,$permissionsExist).">
                        <span class='perm-checkmark'></span>
                        <span class='perm-check-text'>".nameFormat($groupName)."</span>
                    </label>
                    ".group($value,$permissionsExist,$childClass.' '.$key)."
                </li>";
            }
        }
        $html3 .= '</ul>';
    }
    return $html1.$html2.$html3;
}
?>

@push('stylesheets')
<style>
.content-header { display: none !important; }

/* Sub-module tree */
.perm-sub-ul { list-style:none; padding:0 0 0 22px; margin:4px 0 0; }
.perm-sub-item { padding:4px 0; }
.perm-check-label {
    display:inline-flex; align-items:center; gap:10px; cursor:pointer;
    font-size:12px; font-weight:800; color:#374151; user-select:none;
    letter-spacing:0.7px; text-transform:uppercase;
}
.perm-check-label input[type=checkbox] { display:none; }
.perm-checkmark {
    width:20px; height:20px; border-radius:5px; border:1.5px solid #c9cdd3;
    background:#fff; flex-shrink:0; display:inline-flex; align-items:center;
    justify-content:center; transition:all 0.12s;
}
.perm-check-label input[type=checkbox]:checked ~ .perm-checkmark {
    background:#ea580c; border-color:#ea580c;
    box-shadow:inset 0 1px 0 rgba(255,255,255,0.2);
}
.perm-check-label input[type=checkbox]:checked ~ .perm-checkmark::after {
    content:''; width:5px; height:9px; border:2.5px solid #fff;
    border-top:none; border-left:none; transform:rotate(45deg); display:block; margin-bottom:2px;
}
.perm-check-text { white-space:nowrap; }

/* Module row */
.perm-module-row {
    display:grid; grid-template-columns:1.1fr 1.4fr;
    align-items:start; gap:14px; padding:14px 6px;
    border-top:1px solid #f0f0f2; transition:background 0.15s;
}
.perm-module-row:first-child { border-top:none; }
.perm-module-name {
    font-size:14px; font-weight:800; color:#0f1115;
    display:flex; align-items:center; gap:12px;
}

/* Section card */
.perm-section {
    background:#ffffff; border-radius:12px; border:1px solid #e8e8ec;
    box-shadow:0 1px 2px rgba(15,17,21,0.04),0 6px 18px -8px rgba(15,17,21,0.12);
    margin-bottom:14px; overflow:hidden;
}
.perm-section-header {
    width:100%; padding:14px 18px; background:#fafafb; border:none;
    border-bottom:1px solid #eeeeef;
    display:flex; align-items:center; gap:12px; cursor:pointer;
}
.perm-section-title {
    flex:1 1 0%; text-align:left; font-size:15px; font-weight:800;
    color:#0f1115; letter-spacing:-0.1px;
    display:flex; align-items:center; gap:12px;
}
.perm-section-icon {
    width:32px; height:32px; border-radius:9px;
    background:#ea580c; color:#fff;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
    box-shadow:inset 0 1px 0 rgba(255,255,255,0.25),0 4px 10px -3px rgba(234,88,12,0.45);
}
#collapseModules, #collapseCrud, #collapseWorkTime { padding:0 22px; }
#collapseModules { padding-top:4px; padding-bottom:14px; }

/* CRUD badges */
.crud-grid { display:flex; gap:10px; flex-wrap:wrap; padding:16px 20px; }
.crud-item { display:flex; flex-direction:column; align-items:center; gap:6px; }
.crud-label-text { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; }
.crud-checkbox-wrap { position:relative; }
.crud-checkbox-wrap input[type=checkbox] { display:none; }
.crud-tile {
    width:54px; height:40px; border-radius:10px; border:2px solid #e2e8f0;
    background:#f8fafc; display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:all 0.15s;
}
.crud-checkbox-wrap input[type=checkbox]:checked + .crud-tile {
    background:linear-gradient(135deg,#f97316,#ea580c);
    border-color:#f97316; box-shadow:0 2px 8px rgba(249,115,22,0.3);
}
.crud-tile i { font-size:14px; color:#94a3b8; transition:color 0.15s; }
.crud-checkbox-wrap input[type=checkbox]:checked + .crud-tile i { color:#fff; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    var form = document.getElementById('savePermissionRoleForm');
    if(!form) return;
    form.addEventListener('submit', function(e){
        e.preventDefault();
        document.querySelectorAll('[data-validate]').forEach(function(el){ el.textContent=''; });
        var btn = form.querySelector('[type=submit]');
        if(btn) btn.disabled = true;
        var formData = new FormData(form);
        fetch('{{ route("management.roles.permission.create.store") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if(btn) btn.disabled = false;
            if(res.success) {
                showPermToast(res.payload.message || 'Permissions saved successfully.', 'success');
            } else {
                showPermToast((typeof res.payload === 'string' ? res.payload : 'Something went wrong.'), 'error');
            }
        })
        .catch(function(){
            if(btn) btn.disabled = false;
            showPermToast('An error occurred.', 'error');
        });
    });
});
function showPermToast(msg, type) {
    var old = document.getElementById('permToast');
    if(old) old.remove();
    var bg = type === 'success' ? 'linear-gradient(135deg,#22c55e,#16a34a)' : 'linear-gradient(135deg,#ef4444,#dc2626)';
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
    var t = document.createElement('div');
    t.id = 'permToast';
    t.style.cssText = 'position:fixed;top:24px;right:24px;z-index:99999;background:'+bg+';color:#fff;padding:14px 20px;border-radius:12px;font-size:14px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 6px 20px rgba(0,0,0,0.15);';
    t.innerHTML = '<i class="fa '+icon+'" style="font-size:16px;"></i><span>'+msg+'</span>';
    document.body.appendChild(t);
    setTimeout(function(){ if(t.parentNode) t.parentNode.removeChild(t); }, 3500);
}
// Custom dropdown helpers
var wtVals = { sh:'01', sm:'00', sa:'AM', eh:'01', em:'00', ea:'AM' };
function wtInitFromHidden() {
    function parse24(val) {
        if(!val) return null;
        var p=val.split(':'), h=parseInt(p[0]), m=p[1]||'00';
        var ap=h>=12?'PM':'AM', h12=h%12; if(h12===0)h12=12;
        return {h:String(h12).padStart(2,'0'), m:m, a:ap};
    }
    var sv=parse24(document.getElementById('start_time')?document.getElementById('start_time').value:'');
    var ev=parse24(document.getElementById('end_time')?document.getElementById('end_time').value:'');
    if(sv){wtVals.sh=sv.h;wtVals.sm=sv.m;wtVals.sa=sv.a;}
    if(ev){wtVals.eh=ev.h;wtVals.em=ev.m;wtVals.ea=ev.a;}
}
function wtToggle(ddId, pickerId) {
    var isOpen = document.getElementById(ddId).classList.contains('active');
    document.querySelectorAll('.wt-dd.active').forEach(function(el){ el.classList.remove('active'); });
    document.querySelectorAll('.wt-picker.open').forEach(function(el){ el.classList.remove('open'); });
    if (!isOpen) {
        var dd = document.getElementById(ddId);
        var trigger = dd.querySelector('.wt-dd-trigger');
        var menu = dd.querySelector('.wt-dd-menu');
        var rect = trigger.getBoundingClientRect();
        // Position fixed menu below trigger, centered
        menu.style.top = (rect.bottom + 4) + 'px';
        menu.style.left = (rect.left + rect.width/2) + 'px';
        menu.style.transform = 'translateX(-50%)';
        dd.classList.add('active');
        document.getElementById(pickerId).classList.add('open');
        // Scroll selected into view
        var sel = menu.querySelector('.sel');
        if(sel) setTimeout(function(){ sel.scrollIntoView({block:'nearest'}); }, 10);
    }
}
function wtSelect(ddId, val, pickerId) {
    document.querySelector('#'+ddId+' .wt-dd-trigger').textContent = val;
    document.querySelectorAll('#'+ddId+' .wt-dd-opt').forEach(function(o){ o.classList.remove('sel'); });
    event.target.classList.add('sel');
    document.getElementById(ddId).classList.remove('active');
    document.getElementById(pickerId).classList.remove('open');
    // store val
    var k = ddId.replace('dd_','');
    wtVals[k] = val;
}
function wtAmpm(prefix, val) {
    wtVals[prefix+'a'] = val;
    var wrap = document.getElementById('ampm_'+prefix);
    wrap.querySelectorAll('.wt-ampm-btn').forEach(function(b){ b.classList.remove('sel'); });
    wrap.querySelector('[onclick*="\''+val+'\'"]').classList.add('sel');
    syncTime(prefix==='s'?'start':'end');
}
// Close on outside click
document.addEventListener('click', function(e){
    if(!e.target.closest('.wt-dd')&&!e.target.closest('.wt-picker')){
        document.querySelectorAll('.wt-dd.active').forEach(function(el){el.classList.remove('active');});
        document.querySelectorAll('.wt-picker.open').forEach(function(el){el.classList.remove('open');});
    }
});
// Sync → hidden input (24h)
function syncTime(which) {
    var p = which==='start'?'s':'e';
    var h = parseInt(wtVals[p+'h']), m = wtVals[p+'m'], ap = wtVals[p+'a'];
    if(ap==='AM'){if(h===12)h=0;}else{if(h!==12)h+=12;}
    var hh = String(h).padStart(2,'0');
    document.getElementById(which==='start'?'start_time':'end_time').value = hh+':'+m;
}
// Set picker from 24h string
function setPickerFromVal(which, val24) {
    if(!val24) return;
    var parts=val24.split(':'), h=parseInt(parts[0]), m=parts[1];
    var ap=h>=12?'PM':'AM', h12=h%12; if(h12===0)h12=12;
    var hv=String(h12).padStart(2,'0');
    var p=which==='start'?'s':'e';
    wtVals[p+'h']=hv; wtVals[p+'m']=m; wtVals[p+'a']=ap;
    document.querySelector('#dd_'+p+'h .wt-dd-trigger').textContent=hv;
    document.querySelector('#dd_'+p+'m .wt-dd-trigger').textContent=m;
    document.querySelectorAll('#dd_'+p+'h .wt-dd-opt').forEach(function(o){o.classList.toggle('sel',o.textContent.trim()===hv);});
    document.querySelectorAll('#dd_'+p+'m .wt-dd-opt').forEach(function(o){o.classList.toggle('sel',o.textContent.trim()===m);});
    var wrap=document.getElementById('ampm_'+p);
    wrap.querySelectorAll('.wt-ampm-btn').forEach(function(b){b.classList.toggle('sel',b.textContent.trim()===ap);});
}
$(document).ready(function(){
    wtInitFromHidden();
    // Sync trigger labels and sel classes from hidden input values
    ['s','e'].forEach(function(p){
        var which=p==='s'?'start':'end';
        var trigger_h=document.querySelector('#dd_'+p+'h .wt-dd-trigger');
        var trigger_m=document.querySelector('#dd_'+p+'m .wt-dd-trigger');
        if(trigger_h) trigger_h.textContent=wtVals[p+'h'];
        if(trigger_m) trigger_m.textContent=wtVals[p+'m'];
        document.querySelectorAll('#dd_'+p+'h .wt-dd-opt').forEach(function(o){o.classList.toggle('sel',o.textContent.trim()===wtVals[p+'h']);});
        document.querySelectorAll('#dd_'+p+'m .wt-dd-opt').forEach(function(o){o.classList.toggle('sel',o.textContent.trim()===wtVals[p+'m']);});
        var wrap=document.getElementById('ampm_'+p);
        if(wrap) wrap.querySelectorAll('.wt-ampm-btn').forEach(function(b){b.classList.toggle('sel',b.textContent.trim()===wtVals[p+'a']);});
        syncTime(which);
    });
});

// Extend is_twentyFour_Hour with toggle UI + custom picker sync
var _orig_is24 = window.is_twentyFour_Hour;
window.is_twentyFour_Hour = function(checkBox, startTime, endTime) {
    var cb = $('.'+checkBox);
    cb.prop('checked', !cb.prop('checked'));
    var checked = cb.filter(':checked').length > 0;
    // Sync hidden inputs directly (original fn reads them)
    if(checked) {
        $('#start_time').val('00:00'); $('#end_time').val('23:59');
        setPickerFromVal('start','00:00'); setPickerFromVal('end','23:59');
        document.querySelectorAll('.wt-dd-trigger,.wt-ampm-btn').forEach(function(el){el.style.pointerEvents='none';el.style.opacity='0.45';});
    } else {
        $('#start_time').val(startTime); $('#end_time').val(endTime);
        setPickerFromVal('start', startTime); setPickerFromVal('end', endTime);
        document.querySelectorAll('.wt-dd-trigger,.wt-ampm-btn').forEach(function(el){el.style.pointerEvents='';el.style.opacity='';});
    }
    // Update toggle UI
    var label = document.getElementById('allDayLabel');
    var dot   = document.getElementById('allDayDot');
    if (!label || !dot) return;
    if (checked) {
        label.style.background = 'linear-gradient(135deg,#f97316,#ea580c)';
        label.style.borderColor = '#f97316';
        label.style.boxShadow = '0 3px 10px rgba(249,115,22,0.3)';
        label.querySelector('div').style.background = 'rgba(255,255,255,0.25)';
        dot.style.left = '20px';
        dot.style.background = '#fff';
    } else {
        label.style.background = '#fff';
        label.style.borderColor = '#e2e8f0';
        label.style.boxShadow = 'none';
        label.querySelector('div').style.background = '#e2e8f0';
        dot.style.left = '3px';
        dot.style.background = '#9ca3af';
    }
};
</script>
@endpush

@section('content')
<section>

    {{-- Page heading — exact spec UI: white card, 42px orange icon tile, count badge --}}
    <div style="background:#ffffff;border-radius:12px;border:1px solid #e8e8ec;box-shadow:0 1px 2px rgba(15,17,21,0.04),0 6px 18px -8px rgba(15,17,21,0.12);padding:18px 22px;display:flex;align-items:center;gap:14px;margin-bottom:14px;">
        <span style="width:42px;height:42px;border-radius:11px;background:#ea580c;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:inset 0 1px 0 rgba(255,255,255,0.25),0 6px 14px -4px rgba(234,88,12,0.45);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4.5 4.5 0 0 0-6 6L3 18l3 3 5.7-5.7a4.5 4.5 0 0 0 6-6L15 12l-3-3 2.7-2.7z"/></svg>
        </span>
        <div style="flex:1 1 0%;">
            <h2 style="margin:0;font-size:18px;font-weight:800;color:#0f1115;letter-spacing:-0.2px;">
                Edit Permissions — <span style="color:#F27420;">{{ $roleName }}</span>
            </h2>
            <p style="margin:2px 0 0;font-size:13px;color:#6b7280;">Assign module access and work time for this role</p>
        </div>
    </div>

    <form id="savePermissionRoleForm" name="savePermissionRoleForm">
    @csrf
    <input type="hidden" name="roleid" id="roleid" value="{{ $roleid }}">

    {{-- ── Modules ─────────────────────────────────────────────── --}}
    <div class="perm-section">
        <div class="perm-section-header" data-bs-toggle="collapse" data-bs-target="#collapseModules">
            <div class="perm-section-title">
                <div class="perm-section-icon"><i class="fa fa-th-large" style="font-size:12px;color:#fff;"></i></div>
                Module Access
            </div>
            <i class="fa fa-angle-down" style="color:#94a3b8;font-size:14px;"></i>
        </div>
        <div id="collapseModules" class="collapse show">
            @foreach($module as $allModule)
            <div class="perm-module-row">
                {{-- Master checkbox + module name --}}
                <div class="perm-module-name">
                    <label class="perm-check-label" style="margin:0;">
                        <input class="parent{{ $allModule }}" id="{{ $allModule }}"
                            value="{{ $allModule.'.*' }}"
                            onClick="selectall(this,'{{ $allModule }}')"
                            {{ isChecked($allModule, $permissionsExist) }}
                            name="checkbox[]" type="checkbox">
                        <span class="perm-checkmark"></span>
                    </label>
                    <span>{{ nameFormat($allModule) }}</span>
                </div>
                {{-- Sub modules --}}
                <div style="font-size:13px;">
                    <?php echo group($leftMenuData[$allModule], $permissionsExist, $allModule); ?>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Edit/Delete Permissions ─────────────────────────────── --}}
    <div class="perm-section">
        <div class="perm-section-header" data-bs-toggle="collapse" data-bs-target="#collapseCrud">
            <div class="perm-section-title">
                <div class="perm-section-icon"><i class="fa fa-lock" style="font-size:12px;color:#fff;"></i></div>
                Edit / Delete Permissions
            </div>
            <i class="fa fa-angle-down" style="color:#94a3b8;font-size:14px;"></i>
        </div>
        <div id="collapseCrud" class="collapse show">
            <?php
                $view=$create=$edit=$delete='';
                if($workTimeData){
                    if($workTimeData['view']==1)   $view='checked';
                    if($workTimeData['create']==1) $create='checked';
                    if($workTimeData['edit']==1)   $edit='checked';
                    if($workTimeData['delete']==1) $delete='checked';
                } else { $view='checked'; }
            ?>
            <div class="crud-grid">
                @foreach([['view','fa-eye','View',$view],['create','fa-plus-circle','Create',$create],['edit','fa-pencil','Edit',$edit],['delete','fa-trash','Delete',$delete]] as $crud)
                <div class="crud-item">
                    <span class="crud-label-text">{{ $crud[2] }}</span>
                    <label class="crud-checkbox-wrap">
                        <input type="checkbox" name="permission[]" value="{{ $crud[0] }}" {{ $crud[3] }}>
                        <div class="crud-tile"><i class="fa {{ $crud[1] }}"></i></div>
                    </label>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Work Time ────────────────────────────────────────────── --}}
    <div class="perm-section">
        <div class="perm-section-header" data-bs-toggle="collapse" data-bs-target="#collapseWorkTime">
            <div class="perm-section-title">
                <div class="perm-section-icon"><i class="fa fa-clock-o" style="font-size:12px;color:#fff;"></i></div>
                Work Time
            </div>
            <i class="fa fa-angle-down" style="color:#94a3b8;font-size:14px;"></i>
        </div>
        <div id="collapseWorkTime" class="collapse show">
            <?php
                $startTime=$endTime='';
                if($workTimeData){
                    $startTime = date('H:i',strtotime($workTimeData['start_time']));
                    $endTime   = date('H:i',strtotime($workTimeData['end_time']));
                }
                $is24 = ($workTimeData && $workTimeData['is_24_hours']==1);
            ?>
            <?php
                // Parse start/end into H, i, ampm for custom pickers
                $sH = $sM = $sA = $eH = $eM = $eA = '';
                if($startTime) {
                    $sH = date('h', strtotime($startTime));
                    $sM = date('i', strtotime($startTime));
                    $sA = date('A', strtotime($startTime));
                }
                if($endTime) {
                    $eH = date('h', strtotime($endTime));
                    $eM = date('i', strtotime($endTime));
                    $eA = date('A', strtotime($endTime));
                }
            ?>
            <style>
            .wt-picker{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid #f6c9a8;border-radius:10px;padding:0 12px;height:46px;box-sizing:border-box;transition:all 0.2s;}
            .wt-picker.open{border-color:#ea580c;box-shadow:0 0 0 3px rgba(234,88,12,0.12);}
            .wt-colon{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:18px;font-weight:800;color:#9ca3af;line-height:1;flex-shrink:0;}
            /* custom dropdown segments */
            .wt-dd{position:relative;display:inline-block;}
            .wt-dd-trigger{height:32px;min-width:34px;display:flex;align-items:center;justify-content:center;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:18px;font-weight:800;color:#0f1115;cursor:pointer;border-radius:7px;padding:0 4px;transition:all 0.15s;user-select:none;}
            .wt-dd-trigger:hover{background:#fff7ed;color:#ea580c;}
            .wt-dd.active .wt-dd-trigger{background:#fff7ed;color:#ea580c;}
            .wt-dd-menu{display:none;position:fixed;background:#fff;border:1.5px solid #fed7aa;border-radius:10px;box-shadow:0 8px 24px rgba(249,115,22,0.13);z-index:99999;min-width:52px;max-height:160px;overflow-y:auto;overflow-x:hidden;scrollbar-width:thin;scrollbar-color:#f97316 #fff7ed;}
            .wt-dd-menu::-webkit-scrollbar{width:4px;}
            .wt-dd-menu::-webkit-scrollbar-track{background:#fff7ed;border-radius:4px;}
            .wt-dd-menu::-webkit-scrollbar-thumb{background:#f97316;border-radius:4px;}
            .wt-dd.active .wt-dd-menu{display:block;}
            .wt-dd-opt{padding:7px 10px;font-size:13px;font-weight:600;color:#374151;cursor:pointer;text-align:center;transition:background 0.1s;}
            .wt-dd-opt:hover{background:#fff7ed;color:#f97316;}
            .wt-dd-opt.sel{background:#fff7ed;color:#f97316;font-weight:700;}
            /* AM/PM toggle — grey segmented look (spec) */
            .wt-ampm-toggle{display:flex;gap:0;border-radius:7px;overflow:hidden;background:#f4f4f6;border:1px solid #eeeeef;padding:2px;height:32px;flex-shrink:0;}
            .wt-ampm-btn{padding:0 10px;font-size:11.5px;font-weight:800;cursor:pointer;color:#6b7280;background:transparent;border-radius:5px;letter-spacing:0.3px;transition:all 0.15s;display:flex;align-items:center;line-height:1;}
            .wt-ampm-btn.sel{background:#ea580c;color:#fff;box-shadow:inset 0 1px 0 rgba(255,255,255,0.25),0 2px 6px rgba(234,88,12,0.35);}
            /* ── Mobile layout ── */
            @media (max-width: 767px) {
                /* Outer wrapper: reduce side padding */
                .wt-outer-wrap { padding: 12px 12px 16px !important; }
                /* Inner tinted card: single-column layout, tighter padding */
                .wt-inner-card { grid-template-columns: 1fr !important; gap: 0 !important; padding: 14px 14px !important; }
                /* Time blocks: full width, spaced by border */
                .wt-time-block { min-width: unset !important; width: 100% !important; flex: unset !important; padding-bottom: 14px !important; }
                /* Divider between start and end time */
                .wt-time-block:first-of-type { border-bottom: 1px dashed #fed7aa !important; margin-bottom: 14px !important; }
                /* Hide horizontal arrow */
                .wt-arrow-sep  { display: none !important; }
                /* All Day: full-width row at bottom with top separator */
                .wt-allday-block {
                    padding-left: 0 !important;
                    padding-top: 12px !important;
                    margin-top: 2px !important;
                    border-top: 1px dashed #fed7aa !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    width: 100% !important;
                }
                .wt-allday-block > label:first-child { margin-bottom: 0 !important; }
                /* Make picker fill full row width on mobile */
                .wt-picker { width: 100% !important; }
            }
            </style>
            <div class="wt-outer-wrap" style="padding:12px 22px 18px;">
                {{-- Tinted inner card — spec: peach bg, grid 1fr 32px 1fr 130px --}}
                <div class="wt-inner-card" style="background:#fff7f0;border:1px solid #f6c9a8;border-radius:12px;padding:16px 18px;display:grid;grid-template-columns:1fr 32px 1fr 130px;gap:14px;align-items:flex-start;">

                    {{-- Hidden native inputs for form submission --}}
                    <input type="hidden" id="start_time" name="start_time" value="{{ $startTime }}">
                    <input type="hidden" id="end_time"   name="end_time"   value="{{ $endTime }}">

                    {{-- Start Time --}}
                    <div class="wt-time-block" style="min-width:0;">
                        <label style="font-size:10.5px;font-weight:800;color:#F27420;letter-spacing:0.8px;text-transform:uppercase;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                            <i class="fa fa-play-circle" style="font-size:11px;"></i> Start Time <span style="color:#ef4444;">*</span>
                        </label>
                        <div class="wt-picker" id="wtp_start">
                            <i class="fa fa-clock-o" style="color:#f97316;font-size:13px;opacity:0.5;flex-shrink:0;"></i>
                            {{-- Hour --}}
                            <div class="wt-dd" id="dd_sh">
                                <div class="wt-dd-trigger" id="dd_sh_label" onclick="wtToggle('dd_sh','wtp_start')">{{ $sH ?: '01' }}</div>
                                <div class="wt-dd-menu" id="dd_sh_menu">
                                    @for($h=1;$h<=12;$h++)
                                    <?php $hv=str_pad($h,2,'0',STR_PAD_LEFT); ?>
                                    <div class="wt-dd-opt {{ ($sH==$h||$sH==$hv)?'sel':'' }}" onclick="wtSelect('dd_sh','{{ $hv }}','wtp_start');syncTime('start')">{{ $hv }}</div>
                                    @endfor
                                </div>
                            </div>
                            <span class="wt-colon">:</span>
                            {{-- Minute --}}
                            <div class="wt-dd" id="dd_sm">
                                <div class="wt-dd-trigger" id="dd_sm_label" onclick="wtToggle('dd_sm','wtp_start')">{{ $sM ?: '00' }}</div>
                                <div class="wt-dd-menu" id="dd_sm_menu">
                                    @for($m=0;$m<=59;$m++)
                                    <?php $mv=str_pad($m,2,'0',STR_PAD_LEFT); ?>
                                    <div class="wt-dd-opt {{ $sM==$mv?'sel':'' }}" onclick="wtSelect('dd_sm','{{ $mv }}','wtp_start');syncTime('start')">{{ $mv }}</div>
                                    @endfor
                                </div>
                            </div>
                            {{-- AM/PM --}}
                            <div class="wt-ampm-toggle" id="ampm_s">
                                <div class="wt-ampm-btn {{ $sA=='AM'||!$sA?'sel':'' }}" onclick="wtAmpm('s','AM')">AM</div>
                                <div class="wt-ampm-btn {{ $sA=='PM'?'sel':'' }}" onclick="wtAmpm('s','PM')">PM</div>
                            </div>
                        </div>
                    </div>

                    {{-- Arrow separator — spec: orange right-arrow --}}
                    <div class="wt-arrow-sep" style="display:flex;align-items:center;justify-content:center;padding-top:32px;color:#F27420;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    </div>

                    {{-- End Time --}}
                    <div class="wt-time-block" style="min-width:0;">
                        <label style="font-size:10.5px;font-weight:800;color:#F27420;letter-spacing:0.8px;text-transform:uppercase;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                            <i class="fa fa-stop-circle" style="font-size:11px;"></i> End Time <span style="color:#ef4444;">*</span>
                        </label>
                        <div class="wt-picker" id="wtp_end">
                            <i class="fa fa-clock-o" style="color:#f97316;font-size:13px;opacity:0.5;flex-shrink:0;"></i>
                            {{-- Hour --}}
                            <div class="wt-dd" id="dd_eh">
                                <div class="wt-dd-trigger" id="dd_eh_label" onclick="wtToggle('dd_eh','wtp_end')">{{ $eH ?: '01' }}</div>
                                <div class="wt-dd-menu" id="dd_eh_menu">
                                    @for($h=1;$h<=12;$h++)
                                    <?php $hv=str_pad($h,2,'0',STR_PAD_LEFT); ?>
                                    <div class="wt-dd-opt {{ ($eH==$h||$eH==$hv)?'sel':'' }}" onclick="wtSelect('dd_eh','{{ $hv }}','wtp_end');syncTime('end')">{{ $hv }}</div>
                                    @endfor
                                </div>
                            </div>
                            <span class="wt-colon">:</span>
                            {{-- Minute --}}
                            <div class="wt-dd" id="dd_em">
                                <div class="wt-dd-trigger" id="dd_em_label" onclick="wtToggle('dd_em','wtp_end')">{{ $eM ?: '00' }}</div>
                                <div class="wt-dd-menu" id="dd_em_menu">
                                    @for($m=0;$m<=59;$m++)
                                    <?php $mv=str_pad($m,2,'0',STR_PAD_LEFT); ?>
                                    <div class="wt-dd-opt {{ $eM==$mv?'sel':'' }}" onclick="wtSelect('dd_em','{{ $mv }}','wtp_end');syncTime('end')">{{ $mv }}</div>
                                    @endfor
                                </div>
                            </div>
                            {{-- AM/PM --}}
                            <div class="wt-ampm-toggle" id="ampm_e">
                                <div class="wt-ampm-btn {{ $eA=='AM'||!$eA?'sel':'' }}" onclick="wtAmpm('e','AM')">AM</div>
                                <div class="wt-ampm-btn {{ $eA=='PM'?'sel':'' }}" onclick="wtAmpm('e','PM')">PM</div>
                            </div>
                        </div>
                    </div>

                    {{-- Spacer + All Day toggle --}}
                    <div class="wt-allday-block" style="min-width:0;">
                        <label style="font-size:10.5px;font-weight:800;color:#F27420;letter-spacing:0.8px;text-transform:uppercase;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                            <i class="fa fa-sun-o" style="font-size:11px;"></i> All Day
                        </label>
                        <label id="allDayLabel" onclick="is_twentyFour_Hour('is_24_hours','{{ $startTime }}','{{ $endTime }}')"
                            style="display:inline-flex;align-items:center;gap:0;cursor:pointer;height:44px;border-radius:10px;border:1.5px solid {{ $is24 ? '#f97316' : '#e2e8f0' }};background:{{ $is24 ? 'linear-gradient(135deg,#f97316,#ea580c)' : '#fff' }};padding:0 6px 0 6px;transition:all 0.25s;box-shadow:{{ $is24 ? '0 3px 10px rgba(249,115,22,0.3)' : 'none' }};min-width:64px;justify-content:center;">
                            {{-- Hidden checkbox --}}
                            <input type="checkbox" class="is_24_hours" name="is_24_hours" value="1"
                                @if($is24) checked @endif
                                style="position:absolute;opacity:0;pointer-events:none;">
                            {{-- Toggle pill --}}
                            <div style="width:44px;height:24px;border-radius:12px;background:{{ $is24 ? 'rgba(255,255,255,0.25)' : '#e2e8f0' }};position:relative;transition:all 0.25s;flex-shrink:0;">
                                <div id="allDayDot" style="position:absolute;top:3px;left:{{ $is24 ? '20px' : '3px' }};width:18px;height:18px;border-radius:50%;background:{{ $is24 ? '#fff' : '#9ca3af' }};transition:all 0.25s;box-shadow:0 1px 3px rgba(0,0,0,0.2);"></div>
                            </div>
                        </label>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ── Action buttons — exact spec UI ──────────────────────── --}}
    <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:0 0 8px;">
        <a href="{{ route('management.settings.index') }}"
            style="height:42px;padding:0 16px;border-radius:10px;background:#ffffff;color:#0f1115;border:1px solid #e8e8ec;font-weight:700;font-size:13.5px;letter-spacing:-0.1px;display:inline-flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 1px 2px rgba(15,17,21,0.04);text-decoration:none;">
            <svg width="15.5" height="15.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            Cancel
        </a>
        <button type="submit"
            style="height:42px;padding:0 16px;border-radius:10px;background:#ea580c;color:#fff;border:1px solid transparent;font-weight:700;font-size:13.5px;letter-spacing:-0.1px;display:inline-flex;align-items:center;justify-content:center;gap:7px;box-shadow:inset 0 1px 0 rgba(255,255,255,0.3),0 1px 2px rgba(234,88,12,0.4),0 6px 16px -4px rgba(234,88,12,0.45);cursor:pointer;outline:none !important;">
            <svg width="15.5" height="15.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
            Save Permissions
        </button>
    </div>

    </form>
</section>
@endsection

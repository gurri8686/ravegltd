@php $flashData = Session::get('redirect'); @endphp
@if(!empty($flashData))
    @if($flashData['type'] == 'success')
    <div class="flash-toast flash-toast-success" id="flashToast">
        <i class="fa fa-check-circle" style="margin-right:8px;"></i>{{ $flashData['message'] }}
    </div>
    @elseif($flashData['type'] == 'error')
    <div class="flash-toast flash-toast-error" id="flashToast">
        <i class="fa fa-times-circle" style="margin-right:8px;"></i>{{ $flashData['message'] }}
    </div>
    @elseif($flashData['type'] == 'warning')
    <div class="flash-toast flash-toast-warning" id="flashToast">
        <i class="fa fa-exclamation-triangle" style="margin-right:8px;"></i>{{ $flashData['message'] }}
    </div>
    @elseif($flashData['type'] == 'info')
    <div class="flash-toast flash-toast-info" id="flashToast">
        <i class="fa fa-info-circle" style="margin-right:8px;"></i>{{ $flashData['message'] }}
    </div>
    @endif
    <script>
    setTimeout(function(){ var el = document.getElementById('flashToast'); if(el){ el.style.opacity='0'; el.style.transform='translateX(100%)'; setTimeout(function(){ el.remove(); },400); } }, 3000);
    </script>
@endif

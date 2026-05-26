@php
    $flashData = Session::get('redirect');
    $flashTitles = ['success' => 'Success', 'error' => 'Error', 'warning' => 'Warning', 'info' => 'Info'];
    $flashIcons  = ['success' => 'fa-check', 'error' => 'fa-times', 'warning' => 'fa-exclamation', 'info' => 'fa-info'];
    $flashType   = !empty($flashData) ? ($flashData['type'] ?? 'info') : null;
@endphp
@if(!empty($flashData) && $flashType)
    <div class="flash-toast flash-toast-{{ $flashType }}" id="flashToast" role="alert">
        <span class="flash-toast-icon"><i class="fa {{ $flashIcons[$flashType] ?? 'fa-info' }}"></i></span>
        <div class="flash-toast-body">
            <div class="flash-toast-title">{{ $flashTitles[$flashType] ?? 'Notice' }}</div>
            <div class="flash-toast-msg">{{ $flashData['message'] }}</div>
        </div>
        <button type="button" class="flash-toast-close" id="flashToastClose" aria-label="Close">
            <i class="fa fa-times"></i>
        </button>
    </div>
    <script>
    (function(){
        var el = document.getElementById('flashToast');
        if(!el) return;
        var timer;
        function dismiss(){
            if(!el) return;
            el.classList.add('flash-hide');
            setTimeout(function(){ if(el){ el.remove(); el = null; } }, 400);
        }
        var closeBtn = document.getElementById('flashToastClose');
        if(closeBtn){ closeBtn.addEventListener('click', function(){ clearTimeout(timer); dismiss(); }); }
        timer = setTimeout(dismiss, 3000);
    })();
    </script>
@endif

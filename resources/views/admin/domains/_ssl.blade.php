@if (is_null($s->ssl_status))
    <span class="badge-dot bd-grey"><i class="bi bi-dash-circle"></i> Not checked</span>
@elseif ($s->ssl_status === 'active')
    <span class="badge-dot bd-green"><i class="bi bi-shield-lock-fill"></i> Active</span>
@elseif ($s->ssl_status === 'expired')
    <span class="badge-dot bd-red"><i class="bi bi-shield-exclamation"></i> Expired</span>
@else
    <span class="badge-dot bd-grey"><i class="bi bi-shield-slash"></i> No SSL</span>
@endif

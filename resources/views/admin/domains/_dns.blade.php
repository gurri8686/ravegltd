@if (is_null($s->verified_at))
    <span class="badge-dot bd-grey"><i class="bi bi-dash-circle"></i> Not checked</span>
@elseif ($s->dns_verified)
    <span class="badge-dot bd-green"><i class="bi bi-check-circle"></i> Verified</span>
@else
    <span class="badge-dot bd-red"><i class="bi bi-x-circle"></i> Unverified</span>
@endif

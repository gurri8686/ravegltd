@if (env('APP_ENV') === 'local')
    <span
    style="
        position: fixed;
        bottom: 40px;
        right: 13px;
        z-index: 999;
        font-size: 14px;
        background: red;
    "class="badge badge-info py-1 px-1">Branch: {{git_branch()}} | Mode: Local</span>
@endif

@if (env('APP_ENV') === 'development')
    <span
    style="
        position: fixed;
        bottom: 40px;
        right: 13px;
        z-index: 999;
        font-size: 16px;
        color:black;
        background: yellow;
    "class="badge badge-info py-1 px-1">Branch: {{git_branch()}} | Mode: Development</span>
@endif

@if (env('APP_ENV') === 'staging')
    <span
    style="
        position: fixed;
        bottom: 40px;
        right: 13px;
        z-index: 999;
        font-size: 16px;
        color:#fff;
        background: green;
    "class="badge badge-info py-1 px-1">Branch: {{git_branch()}} | Mode: Staging</span>
@endif

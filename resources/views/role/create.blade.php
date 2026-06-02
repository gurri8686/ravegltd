    @extends('layouts.main')

    @section('sidebar')
    @include('layouts.sidebars.admin')
    @endsection

    @push('stylesheets')
    <style>
    .content-header { display: none !important; }

    /* ── Mobile-only (≤767px) reference UI — header card + Role Information card.
       Desktop layout untouched. ── */
    @media (max-width: 767px) {
        section { display: block !important; }
        /* Header card (icon + Add New Role + subtitle) */
        section > div:first-child {
            margin-bottom: 12px !important;
            padding: 16px !important;
            border-radius: 16px !important;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important;
            border: 1px solid #eaecf2 !important;
        }
        section > div:first-child > div > div:first-child {
            width: 42px !important; height: 42px !important; border-radius: 12px !important;
        }
        section > div:first-child > div > div:first-child i { font-size: 17px !important; }
        section > div:first-child h1 { font-size: 18px !important; }
        section > div:first-child p { font-size: 12.5px !important; }

        /* Role Information card — strip the bootstrap card chrome, rebuild as clean white card */
        .row.match-height, .row.match-height > .col-md-12 { margin: 0 !important; padding: 0 !important; }
        .row.match-height .card {
            height: auto !important;
            border: 1px solid #eaecf2 !important;
            border-radius: 16px !important;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important;
            margin: 0 !important;
        }
        .card-content, .card-body { padding: 16px !important; }
        /* "Role Information" heading — orange person icon + bold text + divider */
        .form-section {
            display: flex !important; align-items: center !important; gap: 9px !important;
            font-size: 16px !important; font-weight: 800 !important; color: #0f1115 !important;
            padding-bottom: 12px !important; margin-bottom: 18px !important;
            border-bottom: 1px solid #f0f1f4 !important; text-transform: none !important;
        }
        .form-section i { color: rgb(234, 88, 12) !important; font-size: 16px !important; }
        /* Label — uppercase, orange asterisk */
        .form-group label {
            font-size: 11px !important; font-weight: 700 !important; color: #64748b !important;
            letter-spacing: 0.6px !important; text-transform: uppercase !important;
            margin-bottom: 7px !important; display: block !important;
        }
        .form-group label .text-danger { color: rgb(234, 88, 12) !important; }
        /* Input — clean, rounded, orange focus */
        .form-group .form-control {
            height: 46px !important; border: 1.5px solid #e2e8f0 !important; border-radius: 10px !important;
            font-size: 14px !important; padding: 0 14px !important; background: #fff !important;
            color: #1e293b !important; box-shadow: none !important; width: 100% !important;
        }
        .form-group .form-control:focus {
            border-color: rgb(234, 88, 12) !important;
            box-shadow: 0 0 0 3px rgba(234,88,12,0.1) !important; outline: none !important;
        }
        /* Buttons row — full width, Cancel + Save */
        .form-btn-cancel, .form-btn-save { flex: 1 !important; height: 46px !important; justify-content: center !important; }
    }

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
    .form-btn-save:hover { box-shadow: 0 6px 20px rgba(249,115,22,0.45); transform: translateY(-1px); }
    .form-btn-save:active { transform: translateY(0); }
    </style>
    @endpush

    <?php
    //echo '<pre>';print_r($group); die;
    // echo $search_array['permission_module_id'];
    // die;


    ?>

    @push('scripts')
    <x-ajax-form-data
        formId='saveRoleForm'
        :route="route('management.roles.role.create.store')"
        method='POST'
    />
    @endpush

    @section('content')
    <section>
    <div style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;border-radius:14px;background:rgb(234, 88, 12);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(234,88,12,0.25);">
                <i class="fa fa-users" style="color:#fff;font-size:20px;"></i>
            </div>
            <div>
                <h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Add New Role</h1>
                <p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Create a new role</p>
            </div>
        </div>
    </div>
    <div class="row match-height">
        <div class="col-md-12">
            <div class="card" style="height: 988.725px;">
                <!--<div class="card-header">
                    <h4 class="card-title" id="basic-layout-form">Add Role</h4>
                    <a class="heading-elements-toggle"><i class="fa fa-ellipsis-v font-medium-3"></i></a>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i class="feather icon-minus"></i></a></li>
                            <li><a data-action="reload"><i class="feather icon-rotate-cw"></i></a></li>
                            <li><a data-action="expand"><i class="feather icon-maximize"></i></a></li>
                            <li><a data-action="close"><i class="feather icon-x"></i></a></li>
                        </ul>
                    </div>
                </div>-->
                <div class="card-content collapse show">
                    <div class="card-body">

                        <form class="form" id="saveRoleForm" name="saveRoleForm">
                        @csrf
                            <div class="form-body">
                                <h4 class="form-section"><i class="feather icon-user"></i> Role Information</h4>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="name">Role Name <span class="text-danger">*</span></label>
                                            <input type="text" id="name" class="form-control" placeholder="e.g. Sales Representative" name="name">
                                            <div class="row"><div class="col-sm-12" data-validate="name"></div></div>
                                        </div>
                                    </div>

                                </div>

    </div>


                            <div style="display:flex;justify-content:flex-end;align-items:center;gap:12px;padding-top:20px;border-top:1px solid #f1f5f9;">
                                <a href="{{ route('management.settings.index') }}?tab=roles" class="form-btn-cancel">
                                    <i class="fa fa-times" style="font-size:11px;"></i> Cancel
                                </a>
                                <button type="submit" class="form-btn-save">
                                    <i class="fa fa-check"></i> Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </section>
    @endsection

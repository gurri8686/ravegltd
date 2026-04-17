    @extends('layouts.main')

    @section('sidebar')
    @include('layouts.sidebars.admin');
    @endsection

    <?php
    //echo '<pre>';print_r($group); die;
    // echo $search_array['permission_module_id'];
    // die;


    ?>

    @push('scripts')
    <x-ajax-form-data
        formId='saveRoleForm'
        :route="route('store')"
        method='POST'
    />
    @endpush

    @section('content')
    <section>
    <div class="row match-height">
        <div class="col-md-12">
            <div class="card" style="height: 988.725px;">
                <div class="card-header">
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
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">

                        <form class="form" id="saveRoleForm" name="saveRoleForm">
                        @csrf
                            <div class="form-body">
                                <h4 class="form-section"><i class="feather icon-user"></i> Role Info</h4>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="name">Name<span class="text-danger">*</span></label>
                                            <input type="text" id="name" class="form-control" placeholder="Role Name" name="name">
                                            <div class="row"><div class="col-sm-12" data-validate="name"></div></div>
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">




















    <!--Accordion wrapper-->
    <div class="accordion md-accordion accordion-blocks" id="accordionEx78" role="tablist" aria-multiselectable="true">
    @foreach($module as $allModule)
    <div class="card">
        <div class="card-header" style="padding:0px!important" role="tab" id="heading{{$allModule->id}}">
        <!-- Heading -->
        <a data-toggle="collapse" data-parent="#accordionEx78" href="#collapse{{$allModule->id}}" aria-expanded="true" aria-controls="collapse{{$allModule->id}}">
            <h5 class="mt-1 mb-0">
            <span>{{$allModule->title}}</span>
            <i class="fa fa-angle-down rotate-icon"></i>
            </h5>
        </a>
        </div>
        <!-- Card body -->
        <div id="collapse{{$allModule->id}}" class="collapse" role="tabpanel" aria-labelledby="heading{{$allModule->id}}" data-parent="#accordionEx78">
        <div class="card-body">
            <!-- Table responsive wrapper -->
            <div class="table-responsive mx-3">
            <!--Table-->
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>
                    <label for="checkbox" class="mr-2 label-table">#</label>
                    </th>
                    <th class="th-lg"><a>Group Name</a></th>
                </tr>
                </thead>
                <tbody>
                @php
                    $groupData= array();
                    $moduleID =$allModule->id;
                    $groupData =  @$group[$moduleID];
                @endphp
                @if($groupData!='')
                @foreach($groupData as $allGroup)
                <tr>
                    <th scope="row">
                    <input class="form-check-input" type="checkbox" id="checkbox{{$allGroup['id']}}">
                    <label for="checkbox" class="label-table"></label>
                    </th>
                    <td>{{$allGroup['title']}}</td>
                </tr>
                @endforeach
                @endif
                </tbody>
            </table>
            <!--Table-->
            </div>
            <!-- Table responsive wrapper -->
        </div>
        </div>

    </div>
    @endforeach
    </div>
    <!--/.Accordion wrapper-->


    </div></div></div>
    </div>


                            <div class="form-actions text-right">
                                <button type="reset" class="btn btn-warning mr-1">
                                    <i class="fa fa-times" style="font-size:11px;"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-check-square-o"></i> Save
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

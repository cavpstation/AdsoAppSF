@extends('layouts/master_template')
@section('content')
<div id="UpdateProject" v-cloak>
    <div class="row">
        <div class="col-md-12 text-right custom-info-block">
            <span class="pd-5 text-capitalize user-info">
                <i class="fa fa-user text-info" aria-hidden="true"></i>&nbsp;
                @{{filterCustomerName}}
            </span>
            <span class="pd-5 user-info">
                <i class="fa fa-phone-square text-info" aria-hidden="true"></i>&nbsp;
                @{{filterCustomerPhoneNumber}}
            </span>
            <span class="pd-5 user-info"> 
                <i class="fa fa-envelope-square text-info" aria-hidden="true"></i>&nbsp;
                @{{filterCustomerEmail}}
            </span>
        </div>
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body" v-cloak>
                    <form action="{{ route('projectmanagement.project.edit.post', ["id" => $currentProjectId]) }}" method="POST" accept-charset="utf-8" id="EditProjectForm" enctype="multipart/form-data" @submit.prevent="">                             
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="QuickEstimation">Quick Estimation*</label>
                                    <select name="QuickEstimation" class="form-control search-estimates" id="QuickEstimation">
                                        <option></option>
                                        <option v-for="QuickEst in quickEstimates" :value="QuickEst.Id" >@{{QuickEst.ReferenceNumber}} (@{{ QuickEst.Name}})</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="Name">Name*</label>
                                    <input readonly="" type="text" name="Name" id="Name" :value="filterProjectName" class="form-control" placeholder="Ex: Sarika Heights" data-entity-existence-url="{{route('check.projectname')}}"/>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ProjectStatus">Status*</label>
                                    <select name="ProjectStatus" class="form-control search-status" id="ProjectStatus">
                                        <option></option>
                                        <option v-for="(Status, Key) in AllStatus" :value="Key" :selected="Key==selectedStatus">@{{Status}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="Description">Description</label>
                                    <textarea rows="4" name="Description" id="Description" class="form-control no-resize-input" placeholder="Ex: Project description.">{{ $Description }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                 <div class="form-group">
                                    <label> </label>
                                    <div class="site-address-block">
                                        <h5><b>@{{filterSuperBuildArea}}</b> <b>@{{filterUnit}}</b></h5>
                                        <h5>@{{filterSiteProjectName}} @{{filterSiteCity}}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" v-if = "rolesWithUsers.length!=0">
                            <div class="col-md-12">
                                <div class="form-group"> 
                                    <h4>Project Team</h4>
                                </div>
                            </div>
                        </div>
                        <div v-for ="roleCategory in rolesWithUsers">
                            <h4>@{{roleCategory["rolecategory"]}}</h4>
                            <div class="row">
                                <div v-for="(roles, role) in roleCategory['role']" class="col-md-4">
                                    <div class="form-group">
                                        <label :for="'RolesUsersMap['+roles['Id'] + ']'">@{{roles['Title']}}</label>
                                        <select :name="'RolesUsersMap['+roles['Id'] + ']'" class="form-control search-users" :id="'RolesUsersMap['+roles['Id'] + ']'">
                                            <option></option>
                                            <option v-for="(user, userIndex) in roles['users']" :value="user['Id']" :selected="user['Id']==roles['oldUserId']">@{{ user['Name'] }}</option>
                                        </select>
                                    </div>
                                </div> 
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 mr-tp-10">
                                <p>  
                                    <input type="submit" name="EditProjectFormSubmit" value="Update" class="btn btn-primary button-custom" id="EditProjectFormSubmit"/>
                                    <input type="reset" class="btn button-custom" value="Undo" id="EditProjectFormReset"/>
                                </p>
                            </div>
                        </div>
                        <input type="hidden" id="ProjectId" value="{{$currentProjectId}}"/>
                    </form>
                    <div class="form-overlay project-loader" :class="{hidden: ProjectFormOverlay}" id="EditProjectFormOverlay">
                        <div class="large loader"></div>
                        <div class="loader-text">@{{OverLayMessage}}</div>
                    </div>
                    <overlay-notification :form-over-lay="FormOverLay" :notification-icon="NotificationIcon" :notification-message="NotificationMessage" @clearmessage="clearOverLayMessage()" ></overlay-notification>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('/plugins/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/pnIntegration/createproject.css') }}">
@endsection

@section('dynamicScripts')
<script src="{{ asset('js/common.js') }}"></script>
<script src="{{ asset('js/pnIntegration/updateproject.js') }}"></script>
@endsection

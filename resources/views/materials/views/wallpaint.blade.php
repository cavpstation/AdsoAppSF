@extends('layouts/master_template')
@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('/css/materials/view.css') }}" />
<link rel="stylesheet" href="{{ URL::assetUrl("/css/magnific-popup.css")}}">
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h4 class="col-md-12 text-primary mr-tp-4">Material Information</h4>
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="Brand">Brand - Sub Brand</label>
                                <p>{{$Brand." - ".$SubBrand}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Shade Name</label>                            
                                <p>{{$ShadeName}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Shade Code</label>
                                <p>{{$ShadeCode}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Available In</label>
                                @if($AvailableIn !== "N/A")
                                <p>{{implode(', ', $AvailableIn)}}</p>
                                @else
                                <p><small>N/A</small></p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Base</label>
                                @if($Base !== "N/A")
                                <p>{{implode(', ', $Base)}}</p>
                                @else
                                <p><small>N/A</small></p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Finish</label>
                                @if($Finishes !== "N/A")
                                <p>{{implode(', ', $Finishes)}}</p>
                                @else
                                <p><small>N/A</small></p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Colour</label>
                                <p>{{$Colour}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Type</label>
                                <p>{{$Type}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Coverage Per Liter</label>
                                <p>{{$Coverage}} Sq.Ft</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Application Area</label>
                                <p>{{$ApplicationArea}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Washable</label>
                                <p>{{$Washable}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Lead Free</label>
                                <p>{{$LeadFree}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Shade Image</label>
                                @if($FullSheetmage !== "N/A")
                                <?php
                                $fullImageJson = json_encode($FullSheetmage);
                                $PaintImagetag = (count($FullSheetmage) > 1) ? '<i class="ion ion-images gallery-icon"></i>' : '<img src="' . URL::CDN($FullSheetmage[0]["Path"]) . '" alt="Sample Laminate" class="note-thumbnail" title="' . $FullSheetmage[0]["UserFileName"] . '">';
                                ?>
                                <div class="image-link">
                                    <a href="{{URL::CDN($FullSheetmage[0]["Path"])}}" class="FullSheetImages" value="{{$fullImageJson}}"  class="cursor-pointer">
                                        {!! $PaintImagetag !!}
                                    </a>
                                </div>
                                @else
                                <p>{{$FullSheetmage}}</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <span class="badge bg-red durability-badge"><i class="fa fa-link"></i></span>&nbsp;&nbsp;<label for="">Durability</label>
                                @if($Durability !== "N/A")
                                <ul class="pd-lt-0 list-style-none durability-list">
                                    <li class="{{ (($Durability == "Low" || $Durability == "Medium" || $Durability == "High" || $Durability == "Very High" || $Durability == "Best in Class") ? "dark-color": 'light-color') }}">
                                    <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                                    </li>
                                    <li class="{{ (($Durability == "Medium" || $Durability == "High" || $Durability == "Very High" || $Durability == "Best in Class")  ? 'dark-color': 'light-color') }}">
                                    <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                                    </li>
                                    <li class="{{ (($Durability == "High" || $Durability == "Very High" || $Durability == "Best in Class") ? 'dark-color': 'light-color') }}">
                                    <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                                    </li>
                                    <li class="{{ (($Durability == "Very High" || $Durability == "Best in Class") ? 'dark-color': 'light-color') }}">
                                    <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                                    </li>
                                    <li class="{{ (($Durability == "Best in Class") ? 'dark-color': 'light-color') }}">
                                    <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                                    </li>&nbsp;&nbsp;
                                    <li>{{$Durability}}</li>
                                </ul>
                                @else
                                <ul class="pd-lt-0 list-style-none durability-list">
                                    <li class="light-color">
                                    <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                                    </li>
                                    <li class="light-color">
                                    <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                                    </li>
                                    <li class="light-color">
                                    <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                                    </li>
                                    <li class="light-color">
                                    <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                                    </li>
                                    <li class="light-color">
                                    <span class="badge bg-durability"><i class="fa fa-check"></i></span>
                                    </li>&nbsp;&nbsp;
                                    <li>Not Specified</li>
                                </ul>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 col-xs-6">
                            <div class="form-group">
                                <label for="">Tags</label>
                                <p>{{$Tags}}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @if($CatalogueNames !== "N/A")
                <h4 class="col-md-12 text-primary mr-tp-4">Catalogue Information</h4>
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-9 col-xs-9">
                            <div class="box-body table-responsive">
                                <table class="table table-bordered" id="CatalogueReportTable" style="margin: 0px auto;">
                                    <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                                        <tr>
                                        <th class="text-center text-vertical-align pd-rt-8" width="20%">S.No</th>
                                        <th class="text-center text-vertical-align pd-rt-8" width="40%">Catalogue Name</th>
                                        <th class="text-center text-vertical-align pd-rt-8" width="40%">Page No</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($CatalogueNames as $Key => $CatalogueName)
                                        <tr>
                                        <td class="text-center text-vertical-align" width="20%">{{ $Key + 1 }}</td>
                                        <td class="text-center text-vertical-align" width="40%">{{$CatalogueName}}</td>
                                        <td class="text-center text-vertical-align" width="40%">{{$PageNo[$Key]}}</td>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                 @if(!auth()->user()->isCustomer())
                <h4 class="col-md-12 text-primary">Price</h4>
                <div class="box-header">
                     <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Price</label>
                                <p>{{$Price}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Unit</label>
                                <p>{{$Unit}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Discount (%)</label>
                                <p>{{$Discount}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Tax (%) [Eg: GST]</label>                            
                                <p>{{$GST}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Installation Charges (Rs)</label>                            
                                <p>{{$InstallationCharges}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Delivery Charges (Rs)</label>                            
                                <p>{{$DeliveryCharges}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Annual Maintainence Cost (Rs)</label>                            
                                <p>{{$AMC}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Price Range</label>
                                @if($PriceRange !== "N/A")
                                <p>{{implode(', ',$PriceRange)}}</p>
                                @else
                                <p><small>N/A</small></p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-xs-6">
                            <div class="form-group">
                                <label for="">Notes</label>                            
                                <p>{{$Notes}}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('dynamicScripts')
<script src="{{ URL::assetUrl('/js/magnific-popup.js') }}"></script>
<script src="{{ URL::assetUrl('/js/materials/view.js') }}"></script>
@endsection

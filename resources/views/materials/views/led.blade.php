@extends('layouts/master_template')
@section('content')
@section('dynamicStyles')
<link rel="stylesheet" href="{{ asset('/css/materials/view.css') }}" />
@endsection
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
                                <label for="">Product Code</label>
                                <p>{{$ProdCode}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Shape</label>                            
                                <p>{{$Shape}}</p>
                            </div>
                        </div>  
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">CCT</label>                            
                                <p>{{$CCT}}</p>
                            </div>
                        </div> 
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Wattages</label>
                                <p>{{$Wattages}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Lumen Output</label>
                                <p>{{$LumenOutput}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Input Voltage</label>
                                <p>{{$InputVoltage}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Driver Type</label>
                                <p>{{$DriverType}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Voltage Operating Range</label>                            
                                <p>{{$VoltageOperatingRange}}</p>
                            </div>
                        </div>  
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">LED Life</label>                            
                                <p>{{$LEDLife}}</p>
                            </div>
                        </div>  
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Optics</label>
                                <p>{{$Optics}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">CRI</label>
                                <p>{{$CRI}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Beam Angle</label>                            
                                <p>{{$BeamAngle}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Finish</label>                            
                                <p>{{$Finish}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Frame</label>                            
                                <p>{{$Frame}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Accessories</label>                            
                                <p>{{$Accessories}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-xs-6">
                            <div class="form-group">
                                <label for="">Tags</label>
                                <p>{{$Tags}}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <h4 class="col-md-12 text-primary">Cut out Dimension (mm)</h4>
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Length </label>
                                <p>{{$CutOutLength}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Width</label>
                                <p>{{$CutOutWidth}}</p>
                            </div>
                        </div> 
                    </div>
                    <h4 class="col-md-12 text-primary" style="margin-left: -25px;">Dimension (mm)</h4>
                    <div class="row">
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Length </label>
                                <p>{{$Length}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Width</label>
                                <p>{{$Width}}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xs-3">
                            <div class="form-group">
                                <label for="">Height</label>                            
                                <p>{{$Height}}</p>
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

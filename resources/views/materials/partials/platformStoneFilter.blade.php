<div class="row">
    <div class="col-md-3">
        <label for="Category">Material Category</label>
        <select class="form-control" name="Category" id="Category">   
            <option value="">Choose a Category</option>
            @foreach($categories as $Key => $category)
            <option value='{{ $category->Id }}' {{ $category->Slug === $slug ? 'selected="selected"' : '' }}>{{ $category->Name}}</option>
            @endforeach
        </select> 
    </div> 
    <div class="col-md-3">
        <label for="SubBrand">Brand / Sub Brand</label>
        <select class="form-control" name="SubBrand" id="SubBrand">   
            <option value="">Choose a Sub Brand</option>
            @foreach($subbrands as $Key => $subbrand)
            <option value='{{$Key}}'>{{$subbrand}}</option>
            @endforeach
        </select> 
    </div> 
    <div class="col-md-3">
        <label for="PlatformStoneType">Type of Stone</label>
        <select class="form-control" name="PlatformStoneType" id="PlatformStoneType">   
            <option value="">Choose a Type</option>
            @foreach($types as $Key => $type)
            <option value='{{ $type->Id }}'>{{ $type->Name}}</option>
            @endforeach
        </select> 
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="Material">Material</label>
            <select class="form-control" id="Material" name="Material">
                <option value="" selected="selected">Select Material</option>
                <option value="BraQuartz">BraQuartz</option>
                <option value="Granite">Granite</option>
                <option value="SoapStone">SoapStone</option>
                <option value="LimeStone">LimeStone</option>
                <option value="Marble">Marble</option>
                <option value="Laminate">Laminate</option>
                <option value="SolidSurfacing">Solid Surfacing</option>
                <option value="RecycleGlass">Recycle Glass</option>
                <option value="ButcherBlock">Butcher Block</option>
                </select>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="Colour">Colour</label>
            <select class="form-control" id="Colour" name="Colour">
                <option value="" selected="selected">Select Colour</option>
                @foreach($colours as $Key => $colour)
                <option value='{{ $colour->Id }}'>{{ $colour->Name}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="CreatedDate">Created Date</label>
            <div class="input-group">
                <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </div>
                <input type="text" name="CreatedDate" id="CreatedDate" class="form-control date-picker" placeholder="Ex: 01-Jan-2017" readonly="true" />
                <div class="input-group-btn">
                    <button type="button" class="btn btn-addon dropdown-toggle" id="CreatedDateButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-filter"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" id="CreatedDateFilters">
                        <li class="active" data-filter-name="eq">
                            <a href="#">
                                <b class="mr-rt-6">=</b> equal to
                            </a>
                        </li>
                        <li data-filter-name="lt">
                            <a href="#">
                                <b class="mr-rt-6">&lt;</b> less than
                            </a>
                        </li>
                        <li data-filter-name="gt">
                            <a href="#">
                                <b class="mr-rt-6">&gt;</b> greater than
                            </a>
                        </li>
                        <li data-filter-name="le">
                            <a href="#">
                                <b class="mr-rt-6">&le;</b> less than or equal to
                            </a>
                        </li>
                        <li data-filter-name="ge">
                            <a href="#">
                                <b class="mr-rt-6">&ge;</b> greater than or equal to
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="UpdatedDate">Updated Date</label>
            <div class="input-group">
                <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </div>
                <input type="text" name="UpdatedDate" id="UpdatedDate" class="form-control date-picker" placeholder="Ex: 01-Jan-2017" readonly="true" />
                <div class="input-group-btn">
                    <button type="button" class="btn btn-addon dropdown-toggle" id="UpdatedDateButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-filter"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" id="UpdatedDateFilters">
                        <li class="active" data-filter-name="eq">
                            <a href="#">
                                <b class="mr-rt-6">=</b> equal to
                            </a>
                        </li>
                        <li data-filter-name="lt">
                            <a href="#">
                                <b class="mr-rt-6">&lt;</b> less than
                            </a>
                        </li>
                        <li data-filter-name="gt">
                            <a href="#">
                                <b class="mr-rt-6">&gt;</b> greater than
                            </a>
                        </li>
                        <li data-filter-name="le">
                            <a href="#">
                                <b class="mr-rt-6">&le;</b> less than or equal to
                            </a>
                        </li>
                        <li data-filter-name="ge">
                            <a href="#">
                                <b class="mr-rt-6">&ge;</b> greater than or equal to
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

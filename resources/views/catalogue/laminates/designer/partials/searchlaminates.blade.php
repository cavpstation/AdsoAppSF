<div class="box-header with-border" :class="{hidden: ShowSearchLamBody}">
     <h4>Select Laminate</h4>
    <div class="row">
        <div class="col-md-5 col-sm-6 col-xs-12" id="SearchBox">
        <input type="text" 
                class="form-control search" 
                placeholder="Search..." 
                ref="SearchLaminates" 
                onblur="this.placeholder = 'Search...'" 
                name="SearchLaminates" 
                v-model="SearchString" 
                id="SearchLaminates" 
                @keyup.enter="searchLaminates"
                data-api-end-point="{{ route('catalogue.laminates.search') }}">
        </div>
        <div class="col-md-7 col-sm-6 col-xs-12 search-btn">
            <button 
                class="btn btn-primary button-search pd-rt-20 pd-lt-20" 
                @click.prevent="searchLaminates"
                id="SearchLamsBtn"
                data-api-end-point="{{ route('catalogue.laminates.search') }}"
                >Search
            </button>
      </div>
    </div>
    <div class="table-responsive pd-tp-14" v-if="fileteredLaminates.length > 0">
        <table class="table table-bordered table-striped">
            <thead style="border-top: 1px solid #f4f4f4" class="bg-light-blue text-center">
                <tr>
                    <th class="text-center text-vertical-align pd-10" width="2%">#</th>
                    <th class="text-center text-vertical-align" width="8%">Image</th> 
                    <th class="text-center text-vertical-align" width="8%">Brand</th>
                    <th class="text-center text-vertical-align" width="8%">Sub Brand</th>
                    <th class="text-center text-vertical-align" width="10%">Design Name</th>
                    <th class="text-center text-vertical-align" width="8%">Design Number</th>
                    <th class="text-center text-vertical-align" width="8%">Type</th> 
                    <th class="text-center text-vertical-align" width="9%">Surface Finish</th> 
                    <th class="text-center text-vertical-align" width="6%">Glossiness</th> 
                    <th class="text-center text-vertical-align" width="9%">Edgeband Availiability</th> 
                    <th class="text-center text-vertical-align" width="14%">Room</th>
                    <th class="text-center text-vertical-align action-col" width="10%">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(laminate, index) in fileteredLaminates">
                    <td class="text-center text-vertical-align" width="2%">@{{ index+1 }}</td>
                    <td class="text-center text-vertical-align" width="8%"> 
                        <div class="image-link">
                            <a :href="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path">
                                <img :src="CdnUrl+JSON.parse(laminate.FullSheetImage)[0].Path" alt="Sample Laminate" class="note-thumbnail" data-toggle="tooltip" :title="JSON.parse(laminate.FullSheetImage)[0].UserFileName" @click.prevent="initializeGallery(laminate.FullSheetImage)">
                            </a>
                        </div>
                    </td>
                    <td class="text-center text-vertical-align" width="8%" v-html="!_.isNull(laminate.BrandName) ? laminate.BrandName : '<small>N/A</small>'"></td>
                    <td class="text-center text-vertical-align" width="8%" v-html="!_.isNull(laminate.SubBrand) ? laminate.SubBrand : '<small>N/A</small>'"></td>
                    <td class="text-center text-vertical-align" width="10%">@{{ laminate.DesignName }}</td>
                    <td class="text-center text-vertical-align" width="8%">@{{ laminate.DesignNo }}</td>
                    <td class="text-center text-vertical-align" width="8%" v-html="!_.isNull(laminate.CategoryName) ? laminate.CategoryName : '<small>N/A</small>'"></td>
                    <td class="text-center text-vertical-align" width="9%" v-html="!_.isNull(laminate.SurfaceFinish) ? laminate.SurfaceFinish : '<small>N/A</small>'"></td>
                    <td class="text-center text-vertical-align" width="6%">@{{laminate.Glossy === "1" ? "Yes" : "No" }}</td>
                    <td class="text-center text-vertical-align" width="9%">@{{laminate.Edgeband === "1" ? "Yes" : "No" }}</td>
                    <td class="text-center text-vertical-align" width="14%">
                        <select name="Room" id="Room" class="form-control room-area" @change="getRoomId($event)">
                            <option value="">Select Room</option>
                            <option v-for="room in YetToFinalizedRooms" :value="room.Id">@{{room.Name}}</option>
                        </select>
                    </td>
                    <td class="text-vertical-align text-center" width="10%">                             
                       <a 
                            href="javascript:void(0)"
                            :data-laminateid="laminate.LaminateId" 
                            target="_self" 
                            class="cursor-pointer" 
                            data-toggle="tooltip" 
                            title="Shortlist Selection" 
                            id="SelectLaminate"
                        >
                        <i class="fa fa-fw fa-plus-square text-black" aria-hidden="true"></i>
                        </a>
                        <a 
                           :href="CreateCatalogueRoute+'/'+laminate.LaminateId+'/'+ProjectId" 
                           :data-laminateid="laminate.LaminateId" 
                           target="_self" 
                           class="cursor-pointer" 
                           data-toggle="tooltip" 
                           title="Add to Shortlist with Room / Combination" 
                           id="AddToShortlist"
                        >
                        <i class="fa fa-fw fa-cart-plus text-black" aria-hidden="true"></i>
                        </a>
                        <a 
                            class="cursor-pointer ViewLaminate" 
                            data-toggle="tooltip" 
                            title="View Laminate Details" 
                            @click.prevent="openFullViewPopup(laminate.LaminateId)"
                            data-toggle="tooltip" 
                            title="View Laminate Details"
                            data-api-end-point="{{ route('catalogue.laminate.get', ["id" => '']) }}"
                        >
                        <i class="fa fa-eye text-black" aria-hidden="true"></i>
                        </a>
                        <a 
                            :href="CompareCatalogueRoute + '/'+ laminate.LaminateId + '/' + ProjectId" 
                            class="cursor-pointer" 
                            id="CompareLaminate" 
                            data-toggle="tooltip" 
                            title="Compare Laminates"
                        >
                        <i class="fa fa-fw fa-search text-black" aria-hidden="true"></i>
                        </a>
                        
                    </td>
                </tr>
            </tbody>
        </table> 
    </div>
    <div class="pd-tp-14" v-if="(laminates.length < 1 && SearchString.length >= 3)"> 
        <div class="callout callout-info">
            <p><i class="fa fa-fw fa-info-circle"></i>No search results found.</p>
        </div>
    </div>
    <div class="pd-tp-14" v-if="SearchString.length < 3"> 
        <div class="callout callout-warning">
            <p><i class="fa fa-fw fa-warning" aria-hidden="true"></i> Enter at least three letters.</p>
        </div>
    </div>
    <div id="AddCombNotificationArea" class="hidden">
        <div class="alert alert-dismissible"></div>
    </div>
</div> 
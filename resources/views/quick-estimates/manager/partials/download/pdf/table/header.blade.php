<thead>
    <tr style="page-break-inside:avoid!important;font-size:12px;">
        <th colspan="5" class="text-normal" width="55%">
            <span class="text-center no-text-transform">
                <span>All dimensions in feet |</span>
                <span>All amount in Indian Rupees (&#8377;)</span>
            </span>
        </th>
        @foreach ($estimate->totals as $total)
            <th class="text-center text-vertical-align amount-text {{ $total->class }}" width="10%">
                <span class="text-bold">&#8377; {{ $total->amount() }}</span>
                <div class="text-bold">{{ $total->name }}</div>
            </th>
        @endforeach
        <th class="text-center bg-white text-vertical-align speciciations" width="15%"></th>
    </tr>
    <tr class="bg-primary bg-blue text-center" style="page-break-inside:avoid!important;">
        <th class="text-center" width="5%">#</th>
        <th class="text-center" width="30%">Items (Units)</th>
        <th class="text-center" width="5%">
            <span class="header-tooltip" title="Who is going to pay whom?">Pay</span>
        </th>
        <th class="text-center cursor-help" width="5%">
            <span class="header-tooltip" title="Required Quantity, if applicable">Nos</span>
        </th>
        <th class="text-center cursor-help" width="10%">
            <span class="header-tooltip no-text-transform" title="Dimensions of the Item, if applicable">W x H/L x D</span>
        </th>
        @foreach ($estimate->totals as $total)
            <th class="text-center {{ $total->class }}" width="10%">Amount</th>
        @endforeach
        <th class="text-center" width="15%">Notes</th>
    </tr>
</thead>

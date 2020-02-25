<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header />
    <soapenv:Body>
        <hot:HotelSearchAvailabilityReq xmlns:hot="http://www.travelport.com/schema/hotel_v34_0" TargetBranch="P7119574">
            <com:BillingPointOfSaleInfo xmlns:com="http://www.travelport.com/schema/common_v34_0" OriginApplication="UAPI" />
            <hot:HotelSearchLocation>
                <hot:HotelLocation Location="<?=$request->from?>" />
            </hot:HotelSearchLocation>
            <hot:HotelSearchModifiers MaxWait="50000" NumberOfAdults="1">
                <com:PermittedProviders xmlns:com="http://www.travelport.com/schema/common_v34_0">
                    <com:Provider Code="1G" />
                </com:PermittedProviders>
            </hot:HotelSearchModifiers>
            <hot:HotelStay>
                <hot:CheckinDate><?=$request->dep_date?></hot:CheckinDate>
                <hot:CheckoutDate><?=$request->return_date?></hot:CheckoutDate>
            </hot:HotelStay>
        </hot:HotelSearchAvailabilityReq>
    </soapenv:Body>
</soapenv:Envelope>

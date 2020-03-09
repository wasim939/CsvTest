<?xml version="1.0" encoding="UTF-8" ?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <univ:HotelCreateReservationReq xmlns:com="http://www.travelport.com/schema/common_v34_0" xmlns:common_v34_0="http://www.travelport.com/schema/common_v34_0" xmlns:hotel="http://www.travelport.com/schema/hotel_v34_0" xmlns:univ="http://www.travelport.com/schema/universal_v34_0" AuthorizedBy="user" ProviderCode="1G" TargetBranch="P7119574" TraceId="trace" UserAcceptance="true">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI" />
            <com:BookingTraveler Age="46" DOB="1967-11-23" Gender="F" Key="lNzFo4NIQIeU22gx5VKZjA==" Nationality="US" TravelerType="ADT">
                <com:BookingTravelerName First="Charlotte" Last="Broker-Greene" Middle="Jane" Prefix="Ms" />
                <com:PhoneNumber AreaCode="08" CountryCode="61" Location="PER" Number="40003000" Type="Home" />
                <com:Email EmailID="test@travelport.com" Type="Home" />
                <com:Address>
                    <com:AddressName>Charllote</com:AddressName>
                    <com:Street>10 Charlie Street</com:Street>
                    <com:City>Perth</com:City>
                    <com:State>WA</com:State>
                    <com:PostalCode>60000</com:PostalCode>
                    <com:Country>AU</com:Country>
                </com:Address>
            </com:BookingTraveler>
            <com:FormOfPayment Key="jwt2mcK1Qp27I2xfpcCtAw==" Type="Cash" />
            <hotel:HotelRateDetail RatePlanType="<?=$request->RatePlanType?>" />
            <hotel:HotelProperty Availability="<?=$hotelInfo['hotelInfo']['Availability']?>" HotelChain="<?=$hotelInfo['hotelInfo']['HotelChain']?>" HotelCode="<?=$hotelInfo['hotelInfo']['HotelCode']?>" Name="<?=$hotelInfo['hotelInfo']['Name']?>">
                <hotel:PropertyAddress>
                    <hotel:Address><?=$hotelInfo['addressInfo']['address']['streetInfo']?></hotel:Address>
                </hotel:PropertyAddress>
            </hotel:HotelProperty>
            <hot:HotelStay xmlns:hot="http://www.travelport.com/schema/hotel_v34_0">
                <hot:CheckinDate><?=$request->checkin_date?></hot:CheckinDate>
                <hot:CheckoutDate><?=$request->checkout_date?></hot:CheckoutDate>
            </hot:HotelStay>
            <hot:GuestInformation xmlns:hot="http://www.travelport.com/schema/hotel_v34_0" NumberOfRooms="1">
                <hot:NumberOfAdults>1</hot:NumberOfAdults>
            </hot:GuestInformation>
        </univ:HotelCreateReservationReq>
    </soapenv:Body>
</soapenv:Envelope>


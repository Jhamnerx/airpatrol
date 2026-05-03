# API Description

#### 1. 1. 1 Description

#### Client(distributorʼs server) should stored the access_token locally, do NOT get

#### access_token per request, instead, use the local access_token before it become

#### invalid. JIMI server will not allow to access if the request frequency is too high from

#### client.

```
Note:
The access token can be used for about 2 hours(depend on the value for expires_in parameter),
do NOT try to get token for every request.
```

#### 1. 1. 2 Request URL

#### See the unique request URL.

#### Method = jimi.oauth.token.get

#### 1. 1. 3 HTTP Request method

#### POST

#### 1. 1. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

## 1. Access Control

## 1. 1 Get access token

```
Menu On this page
```

```
Name Type Required Description Remark Default
```

```
user_id String Yes User ID
Userʼs
account
```

```
user_pwd_md5 String Yes
```

```
userʼs
password
(md5)
```

```
Lowercase
md5 value.
```

```
expires_in number Yes
```

```
access token
expired
seconds.
```

##### 60-

#### 1. 1. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other failure: Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Object Result
```

#### Result

```
key Type Description
```

```
accessToken string access token, required by any other following interfaces.
```

```
expiresIn string access token expired seconds.
```

```
account string userʼs account.
```

```
appKey string appKey from JIMI.
```

```
refreshToken string Refresh token, which is used for updating accessToken
```

```
time string Token generated time.
```

#### Correct return example ：

#### Return error example ：

#### Postman demo:

```
Note:
Here is the Open API account information required for your integration work:
```

##### {

```
"code": 0 ,
"message": "success",
"result": {
"appKey": "8FB345B8693CCD003CC2DAB61EC8791D",
"account": "jimitest",
"accessToken": "7da3330ec28e3996b6ef4a7e3390ba71",
"expiresIn": 60 ,
"refreshToken": "7da3330ec28e3996b6ef4a7e3390ba71",
"time": "2017-06-15 10:00:00"
}
}
```

##### {

```
"code":"xxx",
"message": "Incorrect user name or password"
}
```

```
json
```

```
json
```

```
account:JMTEST
password:21218cca77804d2ba1922c33e
appKey:8FB345B8693CCD00CE073CAB5F094009339A22A4105B
appSecret:c0aa0226fddc4365a3c67fef45427f8a
```

```
⚠user_id = your tracksolid account.
⚠user_pwd_md5 = md5(your tracksolid account password).
⚠Sign = md5(<app_secrect>your alphabet ordered parameters keyvalue, without equal-sign,
comma and not include sign field<app_secrect>).
```

```
<app_secrect>app_key<app_key>expires_in<expires_in>formatjsonmethodjimi.oauth.token.getsign
_methodmd5timestamp<timestamp>user_id<account>user_pwd_md5<password_md5>v1.0<app_secrect>
```

```
⚠Note that sign is a 32-length upper case string.
For example:
```

1. Replace the content in <> with your account information,and you will get a string like this ：

```
c0aa0226fddc4365a3c67fef45427f8aapp_key8FB345B8693CCD00CE073CAB5F094009339A22A4105B6558expi
res_in7200formatjsonmethodjimi.oauth.token.getsign_methodmd5timestamp2025-05-
10:23:00user_idJMTEST123user_pwd_md521218cca77804d2ba1922c33e0151105v1.0c0aa0226fddc4365a3c
67fef45427f8a
```

2. Sign =
   md5(c0aa0226fddc4365a3c67fef45427f8aapp_key8FB345B8693CCD00CE073CAB5F
   09339A22A4105B6558expires_in7200formatjsonmethodjimi.oauth.token.getsign_methodmd
   5timestamp2025-05-
   08:10:00user_idJMTEST123user_pwd_md521218cca77804d2ba1922c33e0151105v1.0c0aa
   26fddc4365a3c67fef45427f8a)

```
4EE067D88EA65FF2AFD4890955E042CB
```

#### 1. 2. 1 Description

#### This interface is used to update token manually when access token is about to be invalid.

#### 1. 2. 2 Request URL

#### See the unique request URL.

#### Method = jimi.oauth.token.refresh

### 1. 2 Refresh access token

#### 1. 2. 3 HTTP Request method

#### POST

#### 1. 2. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Description Remark Default
```

```
access_token String YES Access token
```

```
refresh_token String YES
Authorized refresh
access token
```

```
expires_in number YES
access token expired
seconds
```

##### 60 -

##### 7200

#### 1. 2. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Object result
```

#### result

```
key Type Description
```

```
accessToken string Access token, required for subsequently interface access.
```

```
expiresIn string Access token expired seconds.
```

```
account string Requested account
```

```
appKey string From JIMI
```

```
refreshToken string Refresh token for updating access token
```

```
key Type Description
```

```
time string Token generated time
```

#### Correct return example ：

#### Return error example ：

#### 2. 1. 1 Description

#### Query all devices of a specified account.

#### 2. 1. 2 Request URL

#### See the unique request URL.

#### Method = jimi.user.device.list

## 2. Device Management

### 2. 1 List all devices of sub-account

##### {

```
"code": 0 ,
"message": "success",
"result": {
"appKey": "8FB345B8693CCD003CC2DAB61EC8791D",
"account": "jimitest",
"accessToken": "7da3330ec28e3996b6ef4a7e3390ba71",
"expiresIn": 60 ,
"refreshToken": "7da3330ec28e3996b6ef4a7e3390ba71",
"time": "2017-06-15 10:00:00"
}
}
```

##### {

```
"code": "xxx",
"message": "Illegal request，token is invalid"
}
```

```
json
```

```
json
```

#### 2. 1. 3 HTTP Request method

#### POST

#### 2. 1. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
Access token for security access JIMI
Server.
```

```
target string Yes - The specified account for inquired.
```

#### 2. 1. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Array[Object] The returned data
```

#### result

```
key Type Description
```

```
imei string Device IMEI
```

```
deviceName string Device name
```

```
mcType string Device model
```

```
mcTypeUseScope string Automobile, electromobile, personal, pet, plane, others
```

```
sim string Sim card number
```

```
expiration string Platform expiration date
```

```
activationTime string Activation time
```

```
key Type Description
```

```
reMark string Remarks
```

```
vehicleName string Vehicle name
```

```
vehicleIcon string Vehicle icon
```

```
vehicleNumber string License plate number
```

```
vehicleModels string Brand
```

```
carFrame string VIN
```

```
driverName string Driver name
```

```
driverPhone string Driver phone number
```

```
enabledFlag int Available or not (1:Available,0:not available)
```

```
engineNumber string Engine number
```

```
deviceGroupId string Device group ID
```

```
deviceGroup string Device group name
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"imei": "868120145233604",
"deviceName": "868120145233604",
"mcType": "GT300L",
"mcTypeUseScope": "personal",
"sim": "415451",
"expiration": "2037-04-01 23:59:59",
"activationTime": "2017-04-01 11:02:20",
"reMark": "test",
"vehicleName": null,
"vehicleIcon": "bus",
"vehicleNumber": "粤 B3604",
"vehicleModels": null,
"carFrame": "2235",
```

```
json
```

#### Return error example ：

#### 2. 2. 1 Description

#### Get device detail information for specific IMEI.

#### 2. 2. 2 Request URL

#### See the unique request URL.

#### Method = jimi.track.device.detail

#### 2. 2. 3 HTTP Request method

#### POST

#### 2. 2. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

### 2. 2 Get device detail information

```
"driverName": "driver",
"driverPhone": "13825036579",
"enabledFlag": 1 ,
"engineNumber": "8565674",
"deviceGroupId": "b54ab3c430864e31a64e54de44c79a1d",
"deviceGroup": "default group"
}
]
}
```

##### {

```
"code": "xxx",
"message": "Account queried doesn’t exist"
}
```

```
json
```

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
Access token for security access JIMI
Server.
```

```
imei string Yes - The specified IMEI for inquired.
```

#### 2. 2. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Object The returned data
```

#### result

```
key Type Description
```

```
imei string Device IMEI
```

```
deviceName string Device name
```

```
account string The account the device belongs to
```

```
customerName string The customer name of the account that the device belongs to
```

```
mcType string Device model
```

```
mcTypeUseScope string Automobile, electromobile, personal, pet, plane, others
```

```
sim string Sim card number
```

```
expiration string Platform expiration date
```

```
user_expiration string
User expiration date, format as: account1,2019 - 01 - 01
account2,2019 - 02 - 02
```

```
activationTime string Activation time
```

```
reMark string Remarks
```

```
key Type Description
```

```
vehicleName string Vehicle name
```

```
vehicleIcon string Vehicle icon
```

```
vehicleNumber string License plate number
```

```
vehicleModels string Vehicle Model
```

```
carFrame string VIN
```

```
driverName string Driver name
```

```
driverPhone string Driver phone number
```

```
enabledFlag int Available or not (1:Available,0:not available)
```

```
engineNumber string Engine number
```

```
iccid string
```

```
importTime string
```

```
imsi string
```

```
licensePlatNo string
```

```
vin string Vehicle frame number
```

```
vehicleBrand string
```

```
fuel_100km string Fuel consumption for per 100km.
```

```
status string 0 - disable 1 - enable
```

```
currentMileage string The current mileage of the device (km)
```

```
deviceGroupId string Device group ID
```

```
deviceGroup string Device group name
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
```

```
json
```

#### Return error example ：

```
"result": {
"imei": "868120145233604",
"deviceName": "868120145233604",
"mcType": "GT300L",
"mcTypeUseScope": "personal",
"sim": "415451",
"expiration": "2037-04-01 23:59:59",
"activationTime": "2017-04-01 11:02:20",
"reMark": "test",
"vehicleName": null,
"vehicleIcon": "bus",
"vehicleNumber": "粤B3604",
"vehicleModels": null,
"carFrame": "2235",
"driverName": "driver",
"driverPhone": "13825036579",
"enabledFlag": 1 ,
"engineNumber": "8565674",
"iccid": "xxxxxxx",
"imsi": "xxxx",
"importTime": "2017-04-01 11:02:20",
"licensePlatNo": "8565674",
"VIN": "xxxxxxx",
"vehicleBrand": "xxxx",
"fuel_100km": "9",
"status": "8565674",
"currentMileage": "102.5",
"deviceGroupId": "b54ab3c430864e31a64e54de44c79a1d",
"deviceGroup": "default group"
}
}
```

##### {

```
"code": "xxx",
"message": "Account queried doesn’t exist"
}
```

```
json
```

#### 2. 3. 1 Description

#### Update user expiration date of devices.

#### 2. 3. 2 Request URL

#### See the unique request URL.

#### Method = jimi.user.device.expiration.update

#### 2. 3. 3 HTTP Request method

#### POST

#### 2. 3. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
access token: used to security access
JIMI server.
```

```
imei_list string Yes - One or multiple devices IMEI.
```

```
new_expiration string Yes New user expiration date for devices.
```

#### 2. 3. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned data
```

#### result list ：

### 2. 3 Update user expiration date

```
Key Type Description
```

```
imei string IMEI of device
```

```
update_result string 0-success, 1-failure
```

```
update_msg string Result information, success and failure reasons
```

#### Correct return example ：

#### Return error example ：

#### 2. 4. 1 Description

#### Update vehicle information by IMEI

### 2. 4 Update vehicle information by IMEI

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"imei": "868120145233604",
"update_result": "0",
"update_msg": "update success"
},
{
"imei": "868120145233605",
"update_result": "1",
"update_msg": "update failed, reason:xxxxxx"
}
]
}
```

##### {

```
"code":xxx,
"message": "Illegal device"
}
```

```
json
```

```
json
```

#### 2. 4. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.device.update

#### 2. 4. 3 HTTP Request method

#### POST

#### 2. 4. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
Access token: used to identify legal
client.
```

```
imei string Yes - Device IMEI
```

```
device_name string No - Device name
```

```
vehicle_name string No - Vehicle name
```

```
vehicle_icon string No - Vehicle icon
```

```
vehicle_number string No - Vehicle plate number
```

```
vehicle_models string No - Vehicle brand
```

```
driver_name string No - Driver name
```

```
driver_phone string No - Driver phone
```

```
device_status string No
0 - disable/1-enable.
Enable/Disable devices.
```

```
sim string No SIM card number
```

```
remarks string No Remarks
```

```
oilWear string No Fuel consumption per 100 kilometers
```

```
deviceGroupId string No Device group id
```

```
mileage string No Mileage, unit: m
```

#### ⚠ Vehicle Icon

```
Icon key Description
```

```
automobile Car
```

```
bus Bus
```

```
per People
```

```
mtc Motorcycle
```

```
truck
```

```
taxi
```

```
plane
```

```
schoolBus
```

```
excavator
```

```
ship
```

```
tricycle
```

```
policeMtc Police Motorcycle
```

```
tractor
```

```
policeCar
```

```
cow
```

```
other
```

#### 2. 4. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned data
```

#### Correct return example ：

#### Return error example ：

#### 2. 5. 1 Description

#### Move devices from one sub-account to another sub-account.

#### 2. 5. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.device.move

#### 2. 5. 3 HTTP Request method

#### POST

#### 2. 5. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

### 2. 5 Move devices

##### {

```
"code": 0 ,
"message": "Vehicle information modification successful",
"result": null
}
```

##### {

```
"code":xxx,
"message": "imei doesn’t exists"
}
```

```
json
```

```
json
```

```
Parameter Type Required Remark Description
```

```
src_account string Yes - Transfer device from account
```

```
dest_account string Yes - Transfer device to account
```

```
imeis string Yes - Device imei
```

```
cleanBindFlag string No - 1: clear data, 0: Do not clear data
```

#### 2. 5. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Array The returned parameters
```

#### Correct return example ：

#### Return error example ：

##### {

```
"code": 1112 ,
"message": " device already exists ",
"result": [
"202205454545454"
],
"data": null
}
```

```
or
```

```
{
"code": 0 ,
"message": " Transfer/Sell Equipment Successfully ",
"result": null,
"data": null
}
```

```
json
```

#### 2. 6. 1 Description

#### Bind the device to the app user.

#### 2. 6. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.device.bind

#### 2. 6. 3 HTTP Request method

#### POST

#### 2. 6. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

```
imei string Yes -
The account to which the geofences
belong
```

```
user_id string Yes - The app user account to be bound
```

#### 2. 6. 5 Response

### 2. 6 Bind app user

##### {

```
"code":xxx,
"message": "no permissions"
}
```

```
json
```

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
10:The device has been bound to a user
1001: Illegal parameters
1002: User or device is incorrect, see message for specific errors
1100: Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

#### Correct return example ：

#### Return error example ：

#### 2. 7. 1 Description

#### Unbind the device from the app use

#### 2. 7. 2 Request URL

### 2. 7 Unbind app user

##### {

```
"code": 0 ,
"message": "success"
}
```

##### {

```
"code": 1100 ,
"message": "The system is busy ",
"result": null,
"data": null
}
```

```
json
```

```
json
```

#### See the unique request URL.

#### Method = jimi.open.device.unbind

#### 2. 7. 3 HTTP Request method

#### POST

#### 2. 7. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

```
imei string Yes -
The account to which the geofences
belong
```

```
user_id string yes -
The app user account you want to
unbind
```

#### 2. 7. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
10:The device has been bound to a user
1001: Illegal parameters
1002: User or device is incorrect, see message for specific errors
1100: Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

#### Correct return example ：

##### {

```
"code": 0 ,
```

```
json
```

#### Return error example ：

#### 2. 8. 1 Description

#### Add device groups.

#### Users can divide devices into different groups for easier management.

#### 2. 8. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.group.create

#### 2. 8. 3 HTTP Request method

#### POST

#### 2. 8. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

### 2. 8 Create device group

```
"message": "success"
}
```

##### {

```
"code": 1100 ,
"message": "The system is busy ",
"result": null,
"data": null
}
```

```
json
```

```
Parameter Type Required Remark Description
```

```
account string Yes -
The account that the created device
group belongs to
```

```
group_name string Yes group name
```

#### 2. 8. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
data object The returned data.
```

#### result

```
Key Type scription
```

```
group_id string ID of the new device group
```

```
group_name string Name of the new device group
```

#### Correct return example ：

#### Return error example ：

##### {

```
"code": 0 ,
"message": "success",
"result": {
"group_id": "534d23f1b28c44319f4f8ba0cda5b7e6",
"group_name": "device group 1"
}
}
```

```
json
```

#### 2. 9. 1 Description

#### Edit device groups.

#### 2. 9. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.group.update

#### 2. 9. 3 HTTP Request method

#### POST

#### 2. 9. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

```
group_id string Yes - ID of the device group being edited
```

```
group_name string Yes Name of the device group being edited
```

#### 2. 9. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

### 2. 9 Edit device group

```
json
```

#### Correct return example ：

#### Return error example ：

#### 2. 10. 1 Description

#### Delete device groups.

#### 2. 10. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.group.delete

#### 2. 10. 3 HTTP Request method

#### POST

#### 2. 10. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

```
group_id string Yes - ID of the device group being deleted
```

#### 2. 10. 5 Response

### 2. 10 Delete device group

##### {

```
"code": 0 ,
"message": "success"
}
```

```
json
```

```
json
```

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

#### Correct return example ：

#### Return error example ：

#### 2. 11. 1 Description

#### Query the device groups of an account.

#### 2. 11. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.group.list

#### 2. 11. 3 HTTP Request method

#### POST

#### 2. 11. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

### 2. 11 Get device group list of an account

##### {

```
"code": 0 ,
"message": "success"
}
```

```
json
```

```
json
```

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

```
account string Yes -
The account that the device groups
belong to
```

#### 2. 11. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
data array[object] The returned data.
```

#### rows list：

```
key Type Description
```

```
group_id string ID of the new device group
```

```
group_name string Name of the new device group
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"group_id": "b2ac10536171474eb0c151c7bb606f3d",
"group_name": "Default group"
},
{
"group_id": "298b949bacbb4badae597ba1fdb629be",
"group_name": "22221"
```

```
json
```

#### Return error example ：

#### 3. 1. 1 Description

#### Get the latest location for all devices under an account.

#### 3. 1. 2 Request URL

#### See the unique request URL.

#### Method = jimi.user.device.location.list

#### 3. 1. 3 HTTP Request method

#### POST

#### 3. 1. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
Access token for security access JIMI
Server.
```

```
target string Yes - Specify the account for inquired.
```

## 3. Tracking Function

### 3. 1 Get location of devices by account

##### },

##### {

```
"group_id": "05cbdd380ca14c2e89b2ab59904f51b7",
"group_name": "CVCVXCV"
}
]
}
```

```
json
```

```
Parameter Type Required Remark Description
```

```
map_type string No -
```

```
map_type=GOOGLE, calibrated by
google calibration.
map_type=null, return origin latitude
and longitude
```

#### 3. 1. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Array[Object] The returned data
```

#### Result data:

```
Key Type Description
```

```
imei string Device IMEI
```

```
deviceName string Device name
```

```
icon string Vehicle icon
```

```
status string Device status 0 - offline; 1 - online
```

```
lat double Longitude (if the device is expired, the value is 0)
```

```
lng double Latitude (if the device is expired, the value is 0)
```

```
expireFlag string Expired or not: 1- not expired; 0 - expired
```

```
activationFlag string Activate or not :1 - Activate; 0 - Not active
```

```
posType string GPS, LBS, WIFI,BEACON
```

```
locDesc string
Location information while the device is positioned by
Bluetooth
```

```
gpsTime string GPS positioning time
```

```
Key Type Description
```

```
hbTime string Heartbeat time
```

```
speed string Speed (unit: km / h)
```

```
accStatus string ACC 0-OFF; 1-ON
```

```
electQuantity string
The power will be calculated based on the model configuration
and voltage.
```

```
powerValue string External voltage(0-100), some models are not supported
```

```
distance string distance from device.
```

```
temperature string temperature (unit:°C)
```

```
trackerOil string Oil quantity of the car（Original voltage value）
```

```
gpsSignal string
```

```
GSM signal strength level
0 - No signal
1 - Extremely week
2 - Week
3 - Strong
4 - Extremely strong
```

```
gpsNum string Number of satellites
```

```
direction string
Moving azimuth angle, 0-360, -1 represents unknown, for
example: 100.12
```

```
currentMileage string Current mileage
```

```
batteryPowerVal String Internal voltage
```

```
confidence int
Positioning Accuracy , divided into 3 levels. 1 represents the
worst, and 3 represents the best.
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"imei": "860121060369660",
```

```
json
```

#### Return error example ：

```
"deviceName": "VL802-69660",
"mcType": "VL802",
"icon": "other",
"status": "1",
"posType": "GPS",
"lat": 22.576562,
"lng": 113.943084,
"hbTime": "2026-03-26 11:25:38",
"accStatus": "1",
"gpsSignal": "4",
"powerValue": null,
"batteryPowerVal": "0.06",
"speed": "0",
"gpsNum": "15",
"gpsTime": "2026-03-18 10:20:37",
"direction": "0",
"activationFlag": "1",
"expireFlag": "1",
"electQuantity": "",
"locDesc": null,
"distance": "-1",
"temperature": null,
"trackerOil": "0.00L",
"trackerOils": null,
"currentMileage": "0.6481649691599485",
"account": "FAETEST",
"customerName": "FAETEST",
"state": null,
"stateTime": null,
"iccid": "898608401024C0669758",
"mac": "",
"chargeStatus": null,
"shutdown": null,
"confidence": null
}
],
"data": null
}
```

#### 3. 2. 1 Description

#### Get the latest location for one or multiple devices.

#### 3. 2. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.location.get

#### 3. 2. 3 HTTP Request method

#### POST

#### 3. 2. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
access token: used to security access
JIMI server.
```

```
imeis string Yes _
```

```
Device IMEI. Separate imei by comma;
POST is recommended if too many
devices (maximum 100 IMEI)
```

```
map_type string No _
```

```
map_type=GOOGLE, calibrated by
google calibration.
map_type=null, return origin latitude
and longitude
```

#### 3. 2. 5 Response

### 3. 2 Get the location of device(s)

##### {

```
"code": "xxx",
"message": "The account does not exist"
}
```

```
json
```

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Array[Object] The returned data
```

#### Result list ：

```
Key Type Description
```

```
imei string Device IMEI
```

```
deviceName string Device name
```

```
account string The account the device belongs to
```

```
customerName string The customer name of the account that the device belongs to
```

```
icon string Vehicle icon
```

```
status string Device status 0, offline; 1, online
```

```
lat double Longitude (if the device expires, the value is 0)
```

```
lng double Latitude (if the device expires, the value is 0)
```

```
expireFlag string 1- not expired; 0 - expired
```

```
activationFlag string 1 - Activate; 0 - Not active
```

```
posType string GPS, LBS, WIFI, BEACON
```

```
locDesc string Location information
```

```
gpsTime string GPS positioning time
```

```
hbTime string Heartbeat time
```

```
speed string Speed (unit: km / h)
```

```
accStatus string ACC 0- off ;1- on
```

```
batteryPowerVal string battery（0-100）, Some device models are not supported
```

```
Key Type Description
```

```
powerValue string
External voltage（0-100), Some device models are not
supported
```

```
distance string distance from device.
```

```
temperature string temperature （unit:°C）
```

```
trackerOil string Vehicle remaining fuel
```

```
currentMileage string The current mileage of the device (km)
```

```
gpsNum string Number of satellites
```

```
gpsSignal string
```

```
GSM signal strength level
0 - No signal
1 - Extremely week
2 - Week
3 - Strong
4 - Extremely strong
```

```
direction string
Moving azimuth angle, 0-360, -1 represents unknown, for
example: 100.12
```

```
electQuantity string
The power will be calculated based on the model configuration
and voltage.
```

```
confidence int
Positioning Accuracy , divided into 3 levels. 1 represents the
worst, and 3 represents the best.
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"imei": "868120145233604",
"deviceName": "868120145233604",
"icon": "bus",
"status": "0",
"posType": "GPS",
"lat": 22.577282,
"lng": 113.916604,
```

```
json
```

#### Return error example ：

#### 3. 3. 1 Description

#### Get the URL of device location showing on the Map.

#### 3. 3. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.location.URL.share

#### 3. 3. 3 HTTP Request method

#### POST

#### 3. 3. 4 Request parameters

### 3. 3 Get sharing location URL

```
"hbTime": "2017-04-26 09:14:50",
"accStatus": "0",
"speed": "0",
"gpsNum": "11",
"gpsTime": "2017-04-26 09:17:46",
"activationFlag": "1",
"expireFlag": "1",
"electQuantity": "60",
"locDesc": null,
"powerValue": null,
"temperature": "86.5",
"trackerOil": null,
"currentMileage": "86.5"
}
]
}
```

##### {

```
"code": "xxx",
"message": "Illegal device"
}
```

```
json
```

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
access token: used to security access
JIMI server.
```

```
imei string Yes _ Device IMEI
```

#### 3. 3. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Object The returned data contains URL information.
```

#### result Object ：

```
Key Type Description
```

```
URL string Device location sharing link
```

#### Correct return example ：

#### Return error example ：

```
 
```

##### {

```
"code": 0 ,
"message": "success",
"result": {
"URL": "data.16180track.com/api/share?ver=2&method=trackDevice_abr&deviceinf
}
}
```

```
json
```

#### 3. 4. 1 Description

#### Get device(s) trips data of specified time range.

#### 3. 4. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.track.mileage

#### 3. 4. 3 HTTP Request method

#### POST

#### 3. 4. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
Access token: used to identify legal
client.
```

```
imeis string Yes - Device imeis, separate by comma.
```

```
begin_time number Yes -
Start time Format: yyyy-MM-dd
HH:mm:ss
```

```
end_time number Yes -
```

```
End time Format: yyyy-MM-dd
HH:mm:ss end_time should be earlier
than current time
```

```
start_row number No - Row number of result set.
```

### 3. 4 Get the mileage data of devices

##### {

```
"code":xxx,
"message": "Illegal device"
}
```

```
json
```

```
Parameter Type Required Remark Description
```

```
page_size number No Records in one page.
```

#### 3. 4. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Array[Object] The returned data
```

```
data Array[Object]
```

```
Summary information：
IMEI ：IMEI of device
totalMileage：The sum of the total mileage of all trips made by
the device within the specified time period
```

#### result list ：

```
Key Type Description
```

```
imei string IMEI of device
```

```
startTime string Start time
```

```
endTime string End time
```

```
startLat string Latitude of start position.
```

```
startLng string Longitude of start position.
```

```
endLat string Latitude of end position.
```

```
endLng string Longitude of end position.
```

```
runTimeSecond int Second of elapsed between start and end position.
```

```
distance double Distance(meter) between start and end position.
```

```
avgSpeed double Average speed
```

#### Correct return example ：

#### Return error example ：

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"imei": "3505831983422342",
"startTime": "2017-04-26 00:00:58",
"endTime": "2017-04-26 00:03:58",
"startLat": 22.577144898887813,
"startLng": 113.91674845964586,
"endLat": 22.677144898887813,
"endLng": 113.92674845964586,
"elapsed": 2130 ,
"distance": 25000 ,
"avgSpeed": 90
},
{
"imei": "3505831983422342",
"startTime": "2017-04-26 00:00:58",
"endTime": "2017-04-26 00:03:58",
"startLat": 22.577144898887813,
"startLng": 113.91674845964586,
"endLat": 22.677144898887813,
"endLng": 113.92674845964586,
"elapsed": 2130 ,
"distance": 25000 ,
"avgSpeed": 90
}
],
"data": [
{
"imei": "3505831983422342",
"totalMileage": 60000
},
{
"imei": "3505831983422342",
"totalMileage": 60000
}
]
}
```

```
json
```

#### 3. 5. 1 Description

#### Get device track data of not more than 7 days, within 3 months.

#### 3. 5. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.track.list

#### 3. 5. 3 HTTP Request method

#### POST

#### 3. 5. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
Access token: used to identify legal
client.
```

```
imei string Yes - Device imei( only 1 each time)
```

```
begin_time number Yes -
Start time Format: yyyy-MM-dd
HH:mm:ss
```

```
end_time number Yes -
```

```
End time Format: yyyy-MM-dd
HH:mm:ss end_time should be earlier
than current time
```

```
map_type string No -
```

```
map_type=GOOGLE, calibrated by
google calibration.
map_type=null, return origin latitude
and longitude
```

#### 3. 5. 5 Response

### 3. 5 Get the track data of device

```
json
```

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Array[Object] The returned data
```

```
data object The returned data
```

#### result list ：

```
Key Type Description
```

```
lng double longitude
```

```
lat double latitude
```

```
gpsTime string GPS positioning time. Format yyyy-MM-dd HH: mm: ss
```

```
direction string Direction, polar coordinates started from due north
```

```
gpsSpeed string GPS speed
```

```
posType string 1-GPS, 2-LBS, 3-WIFI
```

```
satellite string GPS antenna signal strength
```

```
ignition string Ignition status；ON-ACC ON, OFF-ACC OFF
```

```
accStatus string Acc status
```

```
confidence int
Positioning Accuracy , divided into 3 levels. 1 represents the worst,
and 3 represents the best.
```

```
gpsMode int Location data type: 0: Real-time data; 1: Retransmitted data
```

#### data list ：

```
Key Type Description
```

```
mileage string The mileage of the track within the query time
```

#### Correct return example ：

#### Return error example ：

#### 3. 6. 1 Description

### 3. 6 Wi-Fi, Base Station locating analysis

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"lat": 22.576607,
"lng": 113.943084,
"gpsTime": "2026-03-18 09:40:46",
"direction": 0 ,
"gpsSpeed": 0.0,
"posType": 1 ,
"satellite": 0 ,
"ignition": "ON",
"accStatus": "ON",
"gpsMode": 0 ,
"confidence": null
}
],
"data": {
"mileage": 0
}
}
```

##### {

```
"code":xxx,
"message": "IMEI does not exist{353419031939627}"
}
{
" code ":xxx,
"message": "The device has expired{353419031939627}"
}
```

```
json
```

```
json
```

#### Allocate by total devices under the account ( 10 times/day/device. All sub-accounts

#### included)

#### 3. 6. 2 Request URL

#### See the unique request URL.

#### Method = jimi.lbs.address.get

#### 3. 6. 3 HTTP Request method

#### POST

#### 3. 6. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
access token: used to identify legal
client.
```

```
imei number Yes - Device Imei
```

```
lbs number No -
```

```
（wifi/LBS: at least one）
LBS inforamtion group
(mcc,mnc,lac,cell,rssi)， max 7. Each
group has five, which should not be
null and sorted in order.
MCC, China: 460
MNC
LAC information, 2312 23222
CELL code: 23222
RSSI Semaphore-70
```

```
wifi string No -
```

```
（wifi/LBS: at least one）
mac1,rssi1 mac2,rssi2
Mac address, no colon in between.
Rssi signal strength
```

#### 3. 6. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Object The returned data
```

#### Result list ：

```
Key Type Description
```

```
lng string longitude
```

```
lat string latitude
```

```
accuracy string Accuracy, the greater the value the better
```

#### Correct return example ：

#### Return error example ：

##### {

```
"code": 0 ,
"message": "success",
"result": {
"lat": 40.65615416521587,
"lng": 109.89894039833524,
"accuracy": 0
}
}
```

##### {

```
"code":xxx,
"message": "illegal device"
}
```

```
json
```

```
json
```

```
 
```

#### 3. 7. 1 Description

#### Get device(s) parking/idling data of specified time range.

#### 3. 7. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.platform.report.parking

#### 3. 7. 3 HTTP Request method

#### POST

#### 3. 7. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes - accesstoken: used for identifying legal third
```

```
account string Yes - The account to which the new fence belong
```

```
imeis string Yes -
Example values:
869247060001770,869247060001259,869
```

```
start_time string Yes - Start time Format: yyyy-MM-dd HH:mm:ss
```

```
end_time string Yes -
End time Format: yyyy-MM-dd HH:mm:ss e
be earlier than current time
```

```
start_row string Yes - Row number of result set.
```

```
page_size string Yes - Recordsin one page.
```

```
acc_type string Yes - on: get the ldling data;off: get the parking d
```

#### 3. 7. 5 Response

### 3. 7 Get parking/idling data of devices

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string null
```

```
data object The returned data.
```

#### data list ：

```
key Type Description
```

```
totalTime string Request processing time
```

```
dataTotalRows string Total rows
```

```
rows array[object] Data details
```

#### rows list ：

```
key Type Description
```

```
imei string Imei of device
```

```
startTime string Start time
```

```
endTime string End time
```

```
durSecond string Parking time
```

```
lng number Longitude
```

```
lat number Latitude
```

```
addr string Address
```

```
deviceName string Device name
```

```
mcType string Device model
```

```
acc string Acc status
```

```
key Type Description
```

```
stopSecond string Length of parking
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": {
"totalTime": "185641",
"dataTotalRows": "2",
"rows": [
{
"imei": "869247060001739",
"startTime": "2022-12-10T01:05:33.000Z",
"endTime": "2022-12-10T04:17:48.000Z",
"durSecond": "11535",
"lng": 113.943093,
"lat": 22.576748,
"addr": "Shigu Road, 松坪村, Xili Sub-district, Nanshan distri...",
"deviceName": "JC450Pro-01739",
"mcType": "JC450Pro",
"acc": "on",
"stopSecond": "11535"
},
{
"imei": "869247060001739",
"startTime": "2022-12-09T22:13:16.000Z",
"endTime": "2022-12-10T01:04:25.000Z",
"durSecond": "10269",
"lng": 113.943002,
"lat": 22.57649,
"addr": "Shigu Road, 松坪村, Xili Sub-district, Nanshan distri...",
"deviceName": "JC450Pro-01739",
"mcType": "JC450Pro",
"acc": "on",
"stopSecond": "10269"
}
]
```

```
json
```

#### Return error example ：

#### 3. 8. 1 Description

#### Query the latest location of TAG.

#### 3. 8. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.location.getTagMsg

#### 3. 8. 3 HTTP Request method

#### POST

#### 3. 8. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

```
imei string Yes - Imei of TAG device
```

### 3. 8 Get the location of TAG device

##### }

##### }

##### {

```
"code": 1100 ,
"message": "Business exception ",
"result": null,
"data": null
}
```

```
json
```

#### 3. 8. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Array[Object] The returned data.
```

#### result list ：

```
key Type Description
```

```
lng double Longitude
```

```
lat double Latitude
```

```
gpsTime Long GPS positioning time
```

```
directions String course
```

```
gpsSpeed int Speed
```

```
positionType String 1:GPS, 2:LBS, 3:WIFI,5:BEACON
```

```
gpsNum int Number of satellites
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"lng": 113.943054,
"lat": 22.576609,
"gpsTime": 1734593340 ,
"directions": "0",
"gpsSpeed": -1.0,
```

```
json
```

#### Return error example ：

#### 3. 9. 1 Description

#### Refresh TAG data in batch

#### 3. 9. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.location.updateTagDevices

#### 3. 9. 3 HTTP Request method

#### POST

#### 3. 9. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes - accesstoken: used for identifying legal third
```

### 3. 9 Refresh TAG Data in Batch

```
"positionType": "5",
"gpsNum": 3
}
]
}
```

##### {

```
"code": 1100 ,
"message": "Business exception ",
"result": null,
"data": null
}
```

```
json
```

```
 
```

```
Parameter Type Required Remark Description
```

```
imeis string Yes -
```

```
Imei of TAG device Device imei(s), separate
than 100 each time.Supports querying the d
and its sub-account devices.Example
values:8780901703130335,7809017031299
```

#### 3. 9. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result null null
```

```
data null null
```

#### Correct return example ：

#### Return error example ：

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": null
}
```

##### {

```
"code": 1100 ,
"message": "Business exception ",
"result": null,
"data": null
}
```

```
json
```

```
json
```

#### 4. 1. 1 Description

#### Get device live streaming page URL& last position information.

#### 4. 1. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.live.page.url

#### 4. 1. 3 HTTP Request method

#### POST

#### 4. 1. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
Access token: used to identify
legal client.
```

```
imei string Yes - Device IMEI
```

```
type string No
Default
type=1
```

```
Type=1 real-time video
Type=2 Historical video
```

```
voice string No Default 1 0 is disable; 1 is enable
```

#### 4. 1. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

## 4. Media Function

### 4. 1 Get Device Live Steaming Page URL

```
Key Type Description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Object The returned data
```

#### result object：

```
Key Type Description
```

```
lng double longitude
```

```
lat double latitude
```

```
gpsTime string GPS positioning time. Format yyyy-MM-dd HH: mm: ss
```

```
direction string Direction, polar coordinates started from due north
```

```
gpsSpeed string GPS speed
```

```
posType string 1-GPS, 2-LBS, 3-WIFI
```

```
satellite string GPS antenna signal strength
```

```
VIN string VIN
```

```
plateNo string License Plate Number
```

```
UrlCamera string Live streaming page URL
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "Vehicle information modification successful",
"result": {
"lat": 22.577144898887813,
"lng": 113.91674845964586,
"gpsTime": "2017-04-26 00:00:58",
"direction": 0 ,
"gpsSpeed": -1,
"posType": 3 ,
"satellite": 11 ,
"VIN": "V12345",
"plateNo": "ABC-12345",
```

```
json
```

#### Return error example ：

#### 4. 2. 1 Description

#### Send video or photo command to device.

#### 4. 2. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.meida.cmd.send

#### 4. 2. 3 HTTP Request method

#### POST

#### 4. 2. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
Access token: used to
identify legal client.
```

```
imei string Yes - Device IMEI
```

### 4. 2 Send media instruction

```
"UrlCamera": "https://www.domain.com/device/video/35408343202342345",
}
}
```

##### {

```
"code":xxx,
"message": "imei doesn’t exists"
}
```

```
json
```

```
Parameter Type Required Remark Description
```

```
camera string Yes
mediaType=1 Only
limited 1 or 2
```

```
1-front camera
2- inward camera
3- front + inward camera
```

```
mediaType string Yes 1 -photo 2-video
```

```
shootTime string No 0 or 3-10
mediaType=2 Recording
duration 3-10
```

#### 4. 2. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string Description of sending command result.
```

```
result JSON
```

##### {

```
"code": "100",
"data": "1.3.3",
"msg": "Communication successful response",
"cmdSeqNo": "1"
}
Return code:
225: time out
226: Parameter error
227: The command is not executed correctly
228: The device is not online
229: Network error, connection error, etc.
238: Device interrupted
240: Data format error
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "Operation successful",
"result": {
"code": "255",
```

```
json
```

#### Return error example ：

#### 4. 3. 1 Description

#### Send upload history video file command to device.

#### 4. 3. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.history.cmd.send

#### 4. 3. 3 HTTP Request method

#### POST

#### 4. 3. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

### 4. 3 Send history video instruction

```
"data": "1.3.3",
"msg": "通信成功响应",
"cmdSeqNo": "1"
}
}
```

##### {

```
"code": -1,
"message": "Operation successful",
"result": {
"code": "228",
"data": "1.3.3",
"msg": "设备不在线",
"cmdSeqNo": "1"
}
}
```

```
json
```

```
Parameter Type Required Remark Description
```

```
access_token string Yes
Access token: used to identify
legal client.
```

```
imei string Yes - Device IMEI
```

```
type string Yes 1-Full fragment 2-Event fragment
```

```
camera string Yes 1-out 2-in
```

```
fileName string No
type=1 is not
null
Historical video file name
```

```
time string No
type=2 is
not null
```

```
Event time point, time format
yyyy-MM-dd HH:mm:ss
```

#### 4. 3. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string Description of sending command result.
```

```
result JSON
```

##### {

```
"code": "100",
"data": "1.3.3",
"msg": "Communication successful response",
"cmdSeqNo": "1"
}
Return code:
225: time out
226: Parameter error
227: The command is not executed correctly
228: The device is not online
229: Network error, connection error, etc.
238: Device interrupted
240: Data format error
```

#### Correct return example ：

#### Return error example ：

#### 4. 4. 1 Description

#### Get device photo or video URL which capture by camera.

#### 4. 4. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.jimi.media.URL

#### 4. 4. 3 HTTP Request method

#### POST

### 4. 4 Get Device JIMI Photo or Video URL

##### {

```
"code": 0 ,
"message": "Operation successful",
"result": {
"code": "255",
"data": "1.3.3",
"msg": "Successful response",
"cmdSeqNo": "1"
}
}
```

##### {

```
"code": -1,
"message": "Operation successful",
"result": {
"code": "228",
"data": "1.3.3",
"msg": "Device is offline",
"cmdSeqNo": "1"
}
}
```

```
json
```

```
json
```

#### 4. 4. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
Access token: used to identify legal
client.
```

```
imei string Yes - Device IMEI
```

```
camera string Yes 1-front camera 2- inward camera 3 both
```

```
media_type string Yes 1 -photo 2-video 3-both
```

```
start_time date No Start time of photo or video created.
```

```
end_time date No End time of photo or video created.
```

```
token string No
Token used to validate whether can
access photo or video or not.
```

```
page_no int No Zero indexed, 0 by default.
```

```
page_size int No 10 rows by default.
```

#### 4. 4. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned data,could be multiple rows.
```

#### result list ：

```
Key Type Description
```

```
thumb_URL string URL of video or photo thumbnail.
```

```
Key Type Description
```

```
file_URL string URL of video or photo.
```

```
mime_type string Mime type of video or photo
```

```
create_time long Create time of video or photo.Unix timestamp format
```

```
alarm_time long The time at which the alarm was triggered, Unix timestamp format
```

```
media_type string 1-photo 2-video
```

```
camera string 0-front camera 1-inward camera
```

```
file_size int File size of photo or video.
```

#### ⚠Remark: Unix timestamp; Example: 1611105520 = 2021 - 01 - 20 09 : 18 : 40

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "Operation successful",
"result": [
{
"thumb_URL": "http://8.210.205.58:8081/normal/get?fileKey=2021_01_20_09_
"file_URL": "http://8.210.205.58:8081/normal/get?fileKey=357730090564767_
"create_time": 1611105520 ,
"mime_type": "video/mp4",
"media_type": 2 ,
"alarm_time": 1611105469 ,
"camera": 0 ,
"file_size": "12108649"
},
{
"thumb_URL": "http://8.210.205.58:8081/normal/get?fileKey=2021_01_20_08_
"file_URL": "http://8.210.205.58:8081/normal/get?fileKey=357730090564767_
"create_time": 1611101264 ,
"mime_type": "video/mp4",
"media_type": 2 ,
"alarm_time": 1611101173 ,
"camera": 0 ,
"file_size": "48452069"
}
```

```
json
```

#### Return error example ：

#### 4. 5. 1 Description

#### Get Device Media Event URL

#### 4. 5. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.media.event.URL

#### 4. 5. 3 HTTP Request method

#### POST

#### 4. 5. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
Access token: used to identify legal
client.
```

```
imeis String Yes - Device IMEI. Separate imei by comma
```

```
event_type String No - Event Type Id
```

```
media_type String Yes - Media Type : 1 -photo 2-video 3-both
```

### 4. 5 Get Device Media Event URL

##### ]

##### }

##### {

```
"code":xxx,
"message": "imei doesn’t exists"
}
```

```
json
```

```
Parameter Type Required Remark Description
```

```
start_time String Yes - Start time of photo or video created.
```

```
end_time String Yes - End time of photo or video created.
```

```
page_no int Yes - page number
```

```
page_size int Yes - Records in one page.
```

#### 4. 5. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string
```

```
data Object The returned data
```

#### data list：

```
Key Type Description
```

```
currentPage int currentPage
```

```
pageSize int pageSize
```

```
startRow int startRow
```

```
endRow int endRow
```

```
totalRecord int totalRecord
```

```
totalPage int totalPage
```

```
result Array[Object]
```

#### result ：

```
Key Type Description
```

```
imei String
```

```
event_type_id String Event type id
```

```
event_type String Event type
```

```
lat String latitude
```

```
lng String longitude
```

```
alarm_time Long
The time at which the alarm was triggered, Unix timestamp
format
```

```
create_time Long Create time of video or photo.Unix timestamp format
```

```
fileList Array[Object] File List
```

#### fileList ：

```
Key Type Description
```

```
media_type int Media Type: 1 -photo 2-video 3-both
```

```
mime_type String Mime type of video or photo : video/mp4，image/jpeg
```

```
thumb_URL String URL of video or photo thumbnail.
```

```
file_URL String File URL
```

```
camera int Channel
```

```
file_size String File size
```

#### ⚠Remark: Unix timestamp; Example: 1611105520 = 2021 - 01 - 20 09 : 18 : 40

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": {
"currentPage": 1 ,
"pageSize": 3 ,
```

```
json
```

"startRow": 0 ,
"endRow": 0 ,
"totalRecord": 72 ,
"totalPage": 24 ,
"result": [
{
"imei": "862798051058689",
"event_type_id": "147",
"event_type": "Collision Alert(DVR)",
"lat": "22.576639",
"lng": "113.943077",
"alarm_time": 1756450743 ,
"create_time": 1756450743 ,
"fileList": [
{
"media_type": 2 ,
"mime_type": "video/mp4",
"thumb_URL": "https://sg-file.heytrack.com/normal/get?fileKey
"file_URL": "https://sg-file.heytrack.com/normal/get?fileKey
"camera": 1 ,
"file_size": "17495844"
}
]
},
{
"imei": "862798051058689",
"event_type_id": "143",
"event_type": "Distraction Alert",
"lat": "22.576551",
"lng": "113.943057",
"alarm_time": 1756261402 ,
"create_time": 1756261402 ,
"fileList": [
{
"media_type": 2 ,
"mime_type": "video/mp4",
"thumb_URL": "https://sg-file.heytrack.com/normal/get?fileKey
"file_URL": "https://sg-file.heytrack.com/normal/get?fileKey
"camera": 2 ,
"file_size": "1653648"
}
]
},
{

#### Return error example ：

#### 4. 6. 1 Description

#### Get the device live streaming address

#### 4. 6. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.media.live.stream

#### 4. 6. 3 HTTP Request method

#### POST

### 4. 6 Get Device Live Streaming Address

```
"imei": "862798051058689",
"event_type_id": "143",
"event_type": "Distraction Alert",
"lat": "22.576551",
"lng": "113.943057",
"alarm_time": 1756261217 ,
"create_time": 1756261217 ,
"fileList": [
{
"media_type": 2 ,
"mime_type": "video/mp4",
"thumb_URL": "https://sg-file.heytrack.com/normal/get?fileKey
"file_URL": "https://sg-file.heytrack.com/normal/get?fileKey
"camera": 2 ,
"file_size": "1644248"
} ] } ] } }
```

```
json
```

#### 4. 6. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
```

```
accesstoken: used for
identifying legal third
party
```

```
imei string Yes - Imei of DVR device
```

```
channel string Yes
```

```
Concox Protocol
Devices: e.g., JC261,
JC400 （Channel
numbers start from
0 ）
JT808/1078 Protocol
Devices: e.g., JC371,
JC181, JC182, JC450,
JC451 （Channel
numbers start from
1 ）
```

```
DVR device channel
```

```
appId string Yes -
```

```
Client identification ID,
the same ID is used for
each session, different
sessions use different
IDs, 15 random letters and
numbers in length
```

#### 4. 6. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100: Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
key Type Description
```

```
result string
```

```
Live streaming address
The streaming address websocket and http protocols can be
interchanged, and customers can adjust as needed.
```

#### Correct return example：

#### Wrong return example：

#### 4. 7. 1 Description

#### The historical video list is stored in the device, so it is necessary to send a command to

#### the device to query the historical video list.

#### 4. 7. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.media.history.list.cmd

### 4. 7 Send Command to Device to Query Historical Video List

```
 
```

##### {

```
"code": 0 ,
"message": "success",
"result": "http://113.108.62.203:11014/1/869247060113161.flv?secret=035c73f7-bb6
"data": null
}
```

##### {

```
"code": 1100 ,
"message": "Business exception ",
"result": null,
"data": null
}
```

```
json
```

```
json
```

#### 4. 7. 3 HTTP Request method

#### POST

#### 4. 7. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for
identifying legal third party
```

```
imei string Yes - Imei of DVR device
```

```
channel string Yes
```

```
Concox Protocol
Devices: e.g.,
JC261, JC400
（Channel numbers
start from 0 ）
JT808/1078
Protocol Devices:
e.g., JC371, JC181,
JC182, JC450,
JC451 （Channel
numbers start from
1 ）
```

```
DVR device channel
```

```
dateTime string Yes -
Query date, format: yyyy-
MM-dd
```

```
instructionId string Yes -
```

```
Instruction ID uniquely
identifies an instruction and
is used for the
correspondence between
downlink instructions and
uplink responses. UUID can
be used.
```

```
appId string Yes - Client identification ID, the
same ID is used for each
session, different sessions
use different IDs, 15 random
```

```
Parameter Type Required Remark Description
letters and numbers in
length
```

#### example

#### 4. 7. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100: Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string
```

```
data string
```

#### Correct return example：

#### Wrong return example：

```
 
```

```
imei= 862798052005515 &channel= 0 &dateTime=2025-11-13&instructionId=5bd5e93158ec4dd3b12
```

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": null
}
```

##### {

```
"code": 1100 ,
```

```
bash
```

```
json
```

```
json
```

#### 4. 8. 1 Description

#### ⚠ After the device receives the command to upload the historical video list, it

#### asynchronously uploads the historical file list, so it is necessary to use the instructionId

#### used when issuing the command to query the result.

#### For this interface, if the received code is 1207 , the list reported by the device has not

#### yet reached the system. It is necessary to poll this interface after a short sleep until the

#### code is 0 or the maximum number of polls is reached.

#### It is recommended to query once per second and poll 20 times.

#### 4. 8. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.media.history.list.get

#### 4. 8. 3 HTTP Request method

#### POST

#### 4. 8. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

```
instructionId string Yes - Instruction ID uniquely identifies an
instruction and is used for the
correspondence between downlink
```

### 4. 8 Query Historical Video List

```
"message": "Business exception ",
"result": null,
"data": null
}
```

```
Parameter Type Required Remark Description
instructions and uplink responses. UUID
can be used.
```

#### example

#### 4. 8. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100: Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result array[object] Device returned history list data.
```

#### result list：

```
key Type Description
```

```
channel string DVR device channel
```

```
beginTime string Start time, format: yyyy-MM-dd HH:mm:ss
```

```
endTime string End time, format: yyyy-MM-dd HH:mm:ss
```

```
alarmFlag string For expansion, not used currently
```

```
resourceType string
```

```
For expansion, not used currently
Audio and video resource type (0: audio and video, 1: audio, 2:
video, 3: video or audio and video)
```

```
codeType string
```

```
For expansion, not used currently
Code stream type (0: all code streams, 1: main code stream, 2:
sub-code stream)
```

```
storageType string For expansion, not used currently
Storage type (0: all storages, 1: main storage, 2: disaster recovery
```

```
instructionId=5bd5e93158ec4dd3b123076add7c19f5
```

```
bash
```

```
key Type Description
storage)
```

```
fileSize string File size
```

```
sortBeginTime string Sorting field
```

```
fileName string
```

```
File name, only available for Concox Protocol Devices: e.g.,
JC261, JC400, not available for JT808/1078 Protocol Devices:
e.g., JC371, JC181, JC182, JC450, JC451 Devices
```

#### Correct return example：

#### ⚠Concox Protocol Devices: e.g., JC 261 , JC 400

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"channel": "0",
"beginTime": "2025-11-13 10:34:19",
"endTime": "2025-11-13 10:35:19",
"alarmFlag": null,
"resourceType": null,
"codeType": 2 ,
"storageType": null,
"fileSize": null,
"sortBeginTime": 1763030059000 ,
"fileName": "2025_11_13_10_34_19_01.mp4"
},
{
"channel": "0",
"beginTime": "2025-11-13 10:35:20",
"endTime": "2025-11-13 10:36:20",
"alarmFlag": null,
"resourceType": null,
"codeType": 2 ,
"storageType": null,
"fileSize": null,
"sortBeginTime": 1763030120000 ,
"fileName": "2025_11_13_10_35_20_01.mp4"
```

```
json
```

#### ⚠JT 808 / 1078 Protocol Devices: e.g., JC 371 , JC 181 , JC 182 , JC 450 , JC 451

##### },

##### {

```
"channel": "0",
"beginTime": "2025-11-13 10:36:20",
"endTime": "2025-11-13 10:37:20",
"alarmFlag": null,
"resourceType": null,
"codeType": 2 ,
"storageType": null,
"fileSize": null,
"sortBeginTime": 1763030180000 ,
"fileName": "2025_11_13_10_36_20_01.mp4"
}
],
"data": null
}
```

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"channel": "1",
"beginTime": "2025-11-13 13:55:41",
"endTime": "2025-11-13 13:58:42",
"alarmFlag": 0 ,
"resourceType": 0 ,
"codeType": 2 ,
"storageType": 0 ,
"fileSize": 6630206 ,
"sortBeginTime": 1763042141000 ,
"fileName": null
},
{
"channel": "1",
"beginTime": "2025-11-13 13:58:42",
"endTime": "2025-11-13 14:01:43",
"alarmFlag": 0 ,
"resourceType": 0 ,
```

```
json
```

#### Wrong return example：

#### 4. 9. 1 Description

#### According to the device historical video file list, obtain the streaming address of historical

#### videos.

### 4. 9 Get Device Historical Video Streaming Address

```
"codeType": 2 ,
"storageType": 0 ,
"fileSize": 8325778 ,
"sortBeginTime": 1763042322000 ,
"fileName": null
},
{
"channel": "1",
"beginTime": "2025-11-13 14:01:43",
"endTime": "2025-11-13 14:04:44",
"alarmFlag": 0 ,
"resourceType": 0 ,
"codeType": 2 ,
"storageType": 0 ,
"fileSize": 8383870 ,
"sortBeginTime": 1763042503000 ,
"fileName": null
}
],
"data": null
}
```

##### {

```
"code": 1100 ,
"message": "Business exception ",
"result": null,
"data": null
}
```

```
json
```

#### 4. 9. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.media.history.stream

#### 4. 9. 3 HTTP Request method

#### POST

#### 4. 9. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
```

```
accesstoken:
used for
identifying
legal third
party
```

```
imei string Yes -
Imei of DVR
device
```

```
channal int Yes -
DVR device
channel
```

```
beginTime string No
```

```
For JT/T 808 / GB/T 1078
Protocol Devices (e.g., JC371,
JC181, etc.):Use the beginTime
and endTime fields returned by
the
jimi.device.media.history.list.get
API method to request the video
stream.
```

```
Start time of
JT808/1078
Protocol
Devices,
format: yyyy-
MM-dd
HH:mm:ss
```

```
Parameter Type Required Remark Description
```

```
endTime string No
```

```
For JT/T 808 / GB/T 1078
Protocol Devices (e.g., JC371,
JC181, etc.):Use the beginTime
and endTime fields returned by
the
jimi.device.media.history.list.get
API method to request the video
stream.
```

```
End time of
JT808/1078
Protocol
Devices,
format: yyyy-
MM-dd
HH:mm:ss
```

```
fileNameList string No
```

```
For Concox Protocol Devices
(e.g., JC261, JC400):Use the
fileName field returned by the
jimi.device.media.history.list.get
API method to request the video
stream.
```

```
Concox
Protocol
Devices, file
list, multiple
files are
separated by
English
commas
```

```
appId string Yes -
```

```
Client
identification
ID, the same
ID is used for
each session,
different
sessions use
different IDs,
15 random
letters and
numbers in
length
```

#### Example

#### ⚠Concox Protocol Devices: e.g., JC 261 , JC 400 （ Channel numbers start from 0 ）

#### ⚠JT 808 / 1078 Protocol Devices: e.g., JC 371 , JC 181 , JC 182 , JC 450 , JC 451 （ Channel

#### numbers start from 1 ）

```
 
```

```
imei= 862798052005515 &channel= 0 &beginTime=&endTime=&fileNameList=2025_11_13_10_34_19_
```

```
bash
```

#### 4. 9. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100: Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string Device historical video streaming address
```

#### Example

#### 4. 10. 1 Description

#### Stop streaming.

#### 4. 10. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.media.close.stream

#### 4. 10. 3 HTTP Request method

### 4. 10 Close Streaming

```
 
```

```
imei= 865478070000239 &channel= 1 &beginTime=2025-11-13 13:55:41&endTime=2025-11-13 13:5
```

```
 
```

##### {

```
"code": 0 ,
"message": "success",
"result": "ws://113.108.62.203:11014/1/865478070000239.history.flv?secret=035c73
"data": null
}
```

```
bash
```

```
json
```

#### POST

#### 4. 10. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
```

```
accesstoken: used for
identifying legal third
party
```

```
imei string Yes - Imei of DVR device
```

```
channal int Yes
```

```
Concox Protocol
Devices: e.g., JC261,
JC400 （Channel
numbers start from
0 ）
JT808/1078 Protocol
Devices: e.g., JC371,
JC181, JC182, JC450,
JC451 （Channel
numbers start from
1 ）
```

```
DVR device channel
```

```
type string Yes -
0: real-time video 1:
historical video
```

```
appId string Yes -
```

```
Client identification ID,
the same ID is used for
each session, different
sessions use different
IDs, 15 random letters and
numbers in length
```

#### Example

#### 4. 10. 5 Response

```
imei= 865478070000239 &channel= 1 &type= 1 &appId=W1g3vp27z06a486
```

```
bash
```

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100: Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string Device historical video streaming address
```

#### 5. 1. 1 Description

#### Get command list supported by device

#### 5. 1. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.instruction.list

#### 5. 1. 3 HTTP Request method

#### POST

#### 5. 1. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
accesstoken: used for identifying legal
third party
```

```
imei string Yes - Device imei
```

#### 5. 1. 5 Response

## 5. Command Management

### 5. 1 Get command list supported by device

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned data
```

#### Result list ：

```
Key Type Description
```

```
id string Command code
```

```
orderName string Command name
```

```
orderContent string Command template
```

```
orderExplain string Command explanation
```

```
orderMsg string prompt
```

```
isOffLine string if support offline command 0-no; 1-yes
```

#### Correct return example ：

```
 
```

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"id": 81 ,
"orderName": "SOS setting",
"orderContent": "SOS,A,{0},{1},{2}#",
"orderExplain": "SOS is used for receive alerts and SOS alerts. SOS numb
"orderMsg": "",
"isOffLine": "1"
}
]
}
```

```
json
```

#### Return error example ：

#### 5. 2. 1 Description

#### Send command to device.

#### ⚠Before sending instructions to the device, please use the method

#### jimi.open.instruction.list to query the list of instructions supported by the device.

#### 5. 2. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.instruction.send

#### 5. 2. 3 HTTP Request method

#### POST

#### 5. 2. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
accesstoken: used for identifying
legal third party
```

```
imei string Yes - Device imei
```

```
inst_param_json string Yes -
Command message json character
string
```

#### inst _ param _ json description

### 5. 2 Send command to device

##### {

```
"code":xxx,
"message": "Illegal device"
}
```

```
json
```

```
Parameter Type Required Remark Description
```

```
inst_id string Yes Command code
```

```
inst_template string Yes - Command template
```

```
params Array[string] Yes - Command params string Array
```

```
is_cover Boolean No false
```

```
Whether cover the existed offline
command or not true-cover
false-not cover
```

#### ⚠Example 1

#### ⚠Example 2

#### ⚠Example 3

```
 
```

```
Get command list supported by device：
{
"id": 81 ,
"orderName": "Add SOS Number",
"orderContent": "SOS,A,{0},{1},{2}#",
"orderExplain": "SOS setting",
"orderMsg": null,
"isOffLine": "0"
}
Send command to device：
{"inst_id": "81","inst_template":"SOS,A,{0},{1},{2}#","params":["0528989490","052898
```

```
Get command list supported by device：
{
"id": 163 ,
"orderName": "Fuel/Power Remote Control",
"orderContent": "RELAY,{0}#",
"orderExplain": null,
"orderMsg": null,
"isOffLine": "0"
}
Send command to device：
{"inst_id": "163","inst_template":"RELAY,{0}#","params":["1"],"is_cover":"true"}
```

```
json
```

```
json
```

#### 5. 2. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned parameters
```

#### Correct return example ：

#### Return error example ：

```
User-defined command:
Get command list supported by device：
{
"id": 99 ,
"orderName": "User defined command",
"orderContent": "{0}",
"orderExplain": null,
"orderMsg": null,
"isOffLine": "0"
}
Send command to device：
{"inst_id": "99","inst_template":"{0}","params":["RELAY,1#"],"is_cover":"true"}
```

##### {

```
"code": 0 ,
"message": "command is successfully sent.",
"result": null
}
```

##### {

```
"code": 12005 ,
"message": "Fail to send command. Result code：226",
"result": null
}
```

```
json
```

```
json
```

```
json
```

#### 5. 3. 1 Description

#### Get results of sending command.

#### 5. 3. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.instruction.result

#### 5. 3. 3 HTTP Request method

#### POST

#### 5. 3. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
accesstoken: used for identifying legal
third party
```

```
imei string Yes - Device imei
```

#### 5. 3. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned data
```

#### Result list ：

### 5. 3 Get results of command execution

```
Key Type Description
```

```
codeId string Command code
```

```
code string Command sent
```

```
content string Content replied by device
```

```
isExecute string
```

```
command status:
0: execution failed,
1: successful execution,
3: to be sent,
4: canceled
```

```
sendTime string Time, format：yyyy-MM-dd HH:mm:ss
```

```
sender string sender
```

```
receiveDevice string Received imei
```

```
isOffLine string 0: online 1: offline
```

```
idsource string Command description
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"codeId": "99",
"code": "status#",
"content": "Parameter error",
"isExecute": "4",
"sendTime": "2017-06-19 11:46:00",
"sender": "jimitest",
"receiveDevice": "868120111111117",
"isOffLine": "1",
"idsource": "User-defined command"
}
]
}
```

```
json
```

#### Return error example ：

#### 5. 4. 1 Description

#### Send raw command to device.

#### 5. 4. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.instruction.raw.send

#### 5. 4. 3 HTTP Request method

#### POST

#### 5. 4. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
accesstoken: used for identifying legal
third party
```

```
imei String Yes - Device imei
```

```
raw_cmd string Yes - Raw command data(hex string)
```

#### ⚠ Raw Command Example: 0 B 02 C 3 A 405060708

#### 5. 4. 5 Response

### 5. 4 Send raw command data to device

##### {

```
"code":xxx,
"message": "Illegal device"
}
```

```
json
```

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned parameters
```

#### Correct return example ：

#### Return error example ：

#### 6. 1. 1 Description

#### Create Geo-fence for IMEI

#### 6. 1. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.device.fence.create

## 6. Geofencing Function

### 6. 1 Create Geo-fence for IMEI

##### {

```
"code": 0 ,
"message": "command is successfully sent.",
"result": null
}
```

##### {

```
"code": 12005 ,
"message": "Fail to send command. Result code：226",
"result": null
}
```

```
json
```

```
json
```

#### 6. 1. 3 HTTP Request method

#### POST

#### 6. 1. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
accesstoken: used for identifying legal
third party
```

```
imei string Yes - Device Imei
```

```
fence_name string Yes - Geo-fence name
```

```
alarm_type string Yes - Alarm type (in / out / in, out)
```

```
report_mode string Yes -
Alarm reporting mode, 0: GPRS,1:
SMS+GPRS
```

```
alarm_switch string Yes - Fence alarm switch(ON/OFF)
```

```
lng string Yes - Longitude
```

```
lat string Yes - latitude
```

```
radius string Yes -
Fence radius(1～ 9999 ；unit: 100
meters)
```

```
zoom_level string Yes - Zoom level (3-19)
```

```
map_type string Yes - Map (GOOGLE)
```

#### 6. 1. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
Key Type Description
```

```
result string The returned data. Fence serial number returned if succeed.
```

#### Correct return example ：

#### Return error example ：

#### 6. 2. 1 Description

#### Delete Geo-fence for device.

#### 6. 2. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.device.fence.delete

#### 6. 2. 3 HTTP Request method

#### POST

#### 6. 2. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

### 6. 2 Delete Geo-fence for device

##### {

```
"code": 0 ,
"message": "Successfully create geo-fence.",
"result": "5"
}
```

##### {

```
"code": 41003 ,
"message": "Device is not online, geo-fence creation failed ",
"result": null
}
```

```
json
```

```
json
```

```
Parameter Type Required Remark Description
```

```
access_token string Yes
accesstoken: used for identifying legal
third party
```

```
imei string Yes - Device imei
```

```
instruct_no string Yes - Geo-fence command serial number
```

#### 6. 2. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned data
```

#### Correct return example ：

#### Return error example ：

### 6. 3 Create platform Geo-fence

##### {

```
"code": 0 ,
"message": "delete the geo-fence successfully",
"result": null
}
```

##### {

```
"code": 41003 ,
"message": "The device is not online and geo-fence can’t be deleted",
"result": null
}
```

```
json
```

```
json
```

#### 6. 3. 1 Description

#### Create a platform geofence,the newly created platform geofence belongs to the default

#### group.

#### 6. 3. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.platform.fence.create

#### 6. 3. 3 HTTP Request method

#### POST

#### 6. 3. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes - accesstoken: used for identifying legal thi
```

```
account string Yes - The account to which the new fence belon
```

```
fence_name string Yes - The name of the new fence
```

```
fence_type string Yes
circle or
polygon
Fence type (circle/polygon)
```

```
fence_color string No
default:
#3B7AFF
Fence color, standard RGB16 color column
```

```
geom string Yes -
```

```
Collection of coordinate points
Polygon: Latitude and longitude are separ
locations are separated by '#', such as:
22.581714259546697,113.8946006794475
341832019817 (Need to transcode to Mars
Round: 22.57540001979625, 113.8881480
Note: latitude comes before longitude
```

```
radius string No
```

##### 200-

##### 5000

```
default:
200
```

```
Fence radius, in m, range 200m~5000m,W
be passed in, otherwise it will become a p
```

```
 
```

```
Parameter Type Required Remark Description
```

```
description string No - fence description
```

#### 6. 3. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1114: Fence name already exists
-1:The system is busy
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string null
```

```
data string The returned data. fence_id returned if succeed.
```

#### Correct return example ：

#### Return error example ：

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": "c33b80d46d2b41d588a5afbd6f8b6285"
}
```

##### {

```
"code": 1114 ,
"message": "That name already exists ",
"result": null,
"data": null
}
```

```
json
```

```
json
```

#### 6. 4. 1 Description

#### Edit platform geofence informatio.

#### 6. 4. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.platform.fence.create

#### 6. 4. 3 HTTP Request method

#### POST

#### 6. 4. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes - accesstoken: used for identifying legal thi
```

```
account string Yes - The account to which the fence belongs
```

```
fence_id string Yes - Fence id that needs to be edited
```

```
fence_name string Yes - The name of the new fence
```

```
fence_type string Yes
circle or
polygon
Fence type (circle/polygon)
```

```
fence_color string No
default:
#3B7AFF
Fence color, standard RGB16 color column
```

```
geom string Yes -
```

```
Collection of coordinate points Polygon: L
separated by commas, and multiple locati
as:
22.581714259546697,113.8946006794475
341832019817 (Need to transcode to Mars
Round: 22.57540001979625, 113.8881480
Note: latitude comes before longitude
```

### 6. 4 Edit platform Geo-fence

```
 
```

```
Parameter Type Required Remark Description
```

```
radius string No
```

##### 200-

##### 5000

```
default:
200
```

```
Fence radius, in m, range 200m~5000m,W
be passed in, otherwise it will become a p
```

```
description string No - fence description
```

#### 6. 4. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
-1:The system is busy
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string null
```

```
data string The returned data. fence_id returned if succeed.
```

#### Correct return example ：

#### Return error example ：

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": "c33b80d46d2b41d588a5afbd6f8b6285"
}
```

##### {

```
"code": -1,
"message": "The system is busy ",
"result": null,
"data": null
}
```

```
json
```

```
json
```

#### 6. 5. 1 Description

#### Delete platform geofence.

#### 6. 5. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.platform.fence.delete

#### 6. 5. 3 HTTP Request method

#### POST

#### 6. 5. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

```
account string Yes -
The account to which the new fence
belongs
```

```
fence_id string Yes - Fence id that needs to be deleted
```

#### 6. 5. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
-1:The system is busy
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string null
```

```
data string null
```

### 6. 5 Delete platform Geo-fence

#### Correct return example ：

#### Return error example ：

#### 6. 6. 1 Description

#### Geofence related device.

#### 6. 6. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.platform.fence.bind

#### 6. 6. 3 HTTP Request method

#### POST

#### 6. 6. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

### 6. 6 Geo-fence related device

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": null
}
```

##### {

```
"code": -1,
"message": " The system is busy ",
"result": null,
"data": null
}
```

```
json
```

```
json
```

```
 
```

```
Parameter Type Required Remark Description
```

```
access_token string Yes - accesstoken: used for identifying legal third
```

```
fence_id string Yes -
```

```
imeis string No -
Example
values:869247060001770,8692470600012
```

```
alert_type string No -
```

```
Alarm types, if multiple alarms are configur
separate them with commas ","
1.in: into the fence;
2.out: out of the fence;
3.stayTimeIn: If you do not enter the fence
the alarm will be triggered;
4.stayTimeOut: If you do not leave the fenc
days, the alarm will be triggered;
```

```
stay_time_in int No -
```

```
Do not enter the fence for more than N day
When there is a value here, you need to pa
alert_type.
```

```
stay_time_out int No
```

```
Do not leave the fence for more than N day
alarm.When there is a value here, you need
stayTimeOut in alert_type.
```

#### 6. 6. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string null
```

```
data string The returned data. the number of related devices returned if succeed.
```

#### Correct return example ：

#### Return error example ：

#### 6. 7. 1 Description

#### Query platform geofences of a specified account.

#### 6. 7. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.platform.fence.list

#### 6. 7. 3 HTTP Request method

#### POST

#### 6. 7. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

### 6. 7 List platform geofences of an account

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": "3"
}
```

##### {

```
"code": 1100 ,
"message": "Business exception ",
"result": null,
"data": null
}
```

```
json
```

```
json
```

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying
legal third party
```

```
account string Yes -
The account to which the
geofences belong
```

```
page_no number No
```

##### >= 1

```
default:1
Page number
```

```
page_size number No
```

##### 1-50

```
default:10
Records in one page.
```

#### 6. 7. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result object Number of fences belonging to the account and details of each fence
```

#### result:

```
Parameter Type Description
```

```
total string Number of fences belonging to the account
```

```
rows Array[Object] The returned data
```

#### Rows list:

```
Parameter Type Description
```

```
fence_id string Fence_id
```

```
fence_name string The name of the geofence
```

```
fence_type string Fence type (circle/polygon)
```

```
 
```

```
Parameter Type Description
```

```
fence_color string default: #3B7AFF
```

```
coordinates string
```

```
Collection of coordinate points Polygon: Latitude and longitude are
separated by commas, and multiple locations are separated by ';', suc
as:
22.581714259546697,113.89460067944759;22.57323797629247,113.9
341832019817 (Need to transcode to Mars coordinate system)
Round: 22.57540001979625, 113.88814802356858
Note: latitude comes before longitude
```

```
radius string
Fence radius, in m, range 200m~5000m,When it is a circle, it needs to
be passed in, otherwise it will become a polygon
```

```
description string fence description
```

```
imeis string
Example
values:869247060001770,869247060001259,869247060001804
```

```
alert_type string
```

```
Alarm types, separated by commas
1.in: into the fence;
2.out: out of the fence;
3.stayTimeIn: If you do not enter the fence for more than N days, the
alarm will be triggered;
4.stayTimeOut: If you do not leave the fence for more than N days, the
alarm will be triggered;
```

```
stay_time_in int
Do not enter the fence for more than N days to trigger an alarm. When
there is a value here, you need to pass in stayTimeIn in the alert_type.
```

```
stay_time_out int
Do not leave the fence for more than N days to trigger an alarm. When
there is a value here, you need to pass in stayTimeOut in alert_type.
```

```
account string account
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": {
"total": 294 ,
```

```
json
```

#### Return error example ：

#### 6. 8. 1 Description

#### Query single fence information based on fence id

#### 6. 8. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.platform.fence.detail

### 6. 8 Query single fence information

```
"rows": [
{
"fence_id": "dfab43ea3e7a40e987056c857cccae7b",
"fence_name": "test fence",
"fence_type": "circle",
"fence_color": "#3b7aff",
"coordinates": "22.544382,114.004037",
"radius": "4153",
"description": "",
"imeis": "231011234567990,869247060001655,868598060001827,8692470600
"alert_type": "in",
"stay_time_out": null,
"stay_time_in": null,
"account": "hao001"
}
]
}
}
```

##### {

```
"code": -1,
"message": "The system is busy ",
"result": null,
"data": null
}
```

```
json
```

#### 6. 8. 3 HTTP Request method

#### POST

#### 6. 8. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

```
fence_id string Yes - Unique id of fence
```

#### 6. 8. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result object Detailed information of fence
```

#### result:

```
Parameter Type Description
```

```
fence_id string Fence_id
```

```
fence_name string The name of the geofence
```

```
fence_type string Fence type (circle/polygon)
```

```
fence_color string default: #3B7AFF
```

```
coordinates string Collection of coordinate points Polygon: Latitude and longitude are
separated by commas, and multiple locations are separated by ';', suc
as:
22.581714259546697,113.89460067944759;22.57323797629247,113.9
```

```
 
```

```
Parameter Type Description
341832019817 (Need to transcode to Mars coordinate system)
Round: 22.57540001979625, 113.88814802356858
Note: latitude comes before longitude
```

```
radius string
Fence radius, in m, range 200m~5000m,When it is a circle, it needs to
be passed in, otherwise it will become a polygon
```

```
description string fence description
```

```
imeis string
Example
values:869247060001770,869247060001259,869247060001804
```

```
alert_type string
```

```
Alarm types, separated by commas
1.in: into the fence;
2.out: out of the fence;
3.stayTimeIn: If you do not enter the fence for more than N days, the
alarm will be triggered;
4.stayTimeOut: If you do not leave the fence for more than N days, the
alarm will be triggered;
```

```
stay_time_in int
Do not enter the fence for more than N days to trigger an alarm. When
there is a value here, you need to pass in stayTimeIn in the alert_type.
```

```
stay_time_out int
Do not leave the fence for more than N days to trigger an alarm. When
there is a value here, you need to pass in stayTimeOut in alert_type.
```

```
account string account
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": {
"fence_id": "dfab43ea3e7a40e987056c857cccae7b",
"fence_name": "test fence",
"fence_type": "circle",
"fence_color": "#3b7aff",
"coordinates": "22.544382,114.004037",
"radius": "4153",
"description": "",
```

```
json
```

#### Return error example ：

#### 7. 1. 1 Description

#### Please develop a service that supports submitting data in x-www-form-urlencoded

#### format via POST requests, and provide the access URL of the service. JIMI server will

#### push the notification by sending a HTTP request using this URL.

#### 7. 1. 2 Message service List

```
Message Type Description
```

```
jimi.push.device.alarm Alarm data
```

#### Request content ：

## 7. Push Function

### 7. 1 Receive Notification

```
"imeis": "231011234567990,869247060001655,868598060001827,869247060001259",
"alert_type": "in",
"stay_time_out": null,
"stay_time_in": null,
"account": "hao001"
}
}
```

##### {

```
"code": -1,
"message": "The system is busy ",
"result": null,
"data": null
}
```

```
json
```

```
Key Type Description
```

```
msgType String Message type, corresponding to the message service list
```

```
data String The content of the message, corresponding to msgType
```

#### 7. 1. 3 Message content ( jimi.push.device.alarm)

#### Alarms pushed are as follows

```
Key Type Description
```

```
imei string Device imei
```

```
deviceName string Device name
```

```
alarmType string Alarm type Corresponding to alertTypeId in the appendix
```

```
alarmName string Alarm name Corresponding to alertType in the appendix
```

```
lat string Latitude
```

```
lng string Longitude
```

```
alarmTime string Alarm time, format (yyyy-MM-dd HH: mm: ss)
```

#### E.g:

#### Parameter 1 :

#### Key = msgType

#### Value = jimi.push.device.alarm

#### Parameter 2 :

#### Key = data

#### Value = {

#### "imei": " 868120145233604 ",

#### "deviceName": " 868120145233604 ",

#### "alarmType": " 2 ",

#### "alarmName": "Power off alarm",

#### "lat": 40. 65615416521587 ,

#### "lng": 109. 89894039833524 ,

#### "alarmTime": " 2017 - 05 - 08 12 : 00 : 00 "

#### }

#### 7. 2. 1 Description

#### Third-party platform should provide an URL(same as messages push URL) to receive the

#### raw data(please contact us and provide the URL manually), JIMI server will push the raw

#### data by sending a HTTP request using this URL.

#### 7. 2. 2 Message service List

```
Message Type Description
```

```
jimi.open.instruction.raw.receive
Raw data receive message type, different from alarm
message.
```

#### Request content：

```
Key Type Description
```

```
msgType string Message type, corresponding to the message service list
```

```
data string The content of the message, corresponding to msgType
```

#### 7. 2. 3 Message content ( jimi.open.instruction.raw.receive)

#### Alarms pushed are as follows：

```
Key Type Description
```

```
imei string Device imei
```

```
deviceName string Device name
```

```
raw_data string raw data from device.(hex string)
```

#### E.g：

#### Parameter 1 :

#### Key = msgType

#### Value = jimi.open.instruction.raw.receive

#### Parameter 2 :

#### Key = data

#### Value = {

### 7. 2 Push Received Raw Data

#### "imei": " 868120145233604 ",

#### "deviceName": " 868120145233604 ",

#### "raw_data": " 0 A 0 C 0 F 01182 E 0101 "

#### }

#### 8. 1. 1 Description

#### Query CAN bus diagnosis data reported by OBD device.

#### 8. 1. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.obd.list

#### 8. 1. 3 HTTP Request method

#### POST

#### 8. 1. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes - accesstoken: used for identifying legal th
```

```
account string Yes - The account to which the new fence belo
```

```
imeis string Yes -
```

```
Device imei(s), separate by comma. No m
time.Supports querying the data of the ac
account devices.Example
values:869247060001770,86924706000
```

```
start_time string Yes - Start time Format: yyyy-MM-dd HH:mm:s
```

```
end_time string Yes -
```

```
End time Format: yyyy-MM-dd HH:mm:ss
earlier than current time.
Query up to 31 days of data at a time.
```

## 8. OBD

### 8. 1 Get the OBD data of devices

```
 
```

```
Parameter Type Required Remark Description
```

```
page_no int Yes
```

##### >= 1

```
default:1
Page number
```

```
page_size int Yes
```

##### 1-100

```
default:10
Recordsin one page.
```

#### 8. 1. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
data object The returned data.
```

#### data list ：

```
key Type Description
```

```
currentPage int The current page, same as page_no.
```

```
pageSize int Recordsin one page, same as page_size.
```

```
totalRecord int Total rows
```

```
result array[object] Data details
```

#### result list ：

```
key Type Description
```

```
imei string Imei of device
```

```
dataReportTime string The time the data was reported
```

```
odometerReading string Vehicle meter mileage(Unit: KM)
```

```
deviceAccumulatedMileage string Mileage counted by the device
```

```
key Type Description
```

```
remainingFuel string Oil volume
```

```
remainingFuelPercentage string
```

```
Oil percentage The data reported by different
vehicles and devices are different. The Oil volume or
Oil percentage is displayed based on the data
actually reported by the device.
```

```
coolantTemperature string Coolant temperature(Unit: °C)
```

```
vehicleBatterVoltage string External input voltage
```

```
currentRPM string Instantaneous engine speed
```

```
currentSpeed string Current driving speed
```

```
vin string Vehicle Identification Number
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"data": {
"currentPage": 1 ,
"pageSize": 2 ,
"startRow": 0 ,
"endRow": 0 ,
"totalRecord": 2684 ,
"totalPage": 0 ,
"result": [
{
"imei": "158511020000028",
"dataReportTime": "2024-05-09 10:25:00",
"odometerReading": "2049.6",
"deviceAccumulatedMileage": "142.9",
"remainingFuel": null,
"remainingFuelPercentage": "58",
"coolantTemperature": "77",
"vehicleBatterVoltage": "118",
"currentRPM": "5016",
"currentSpeed": "88.5",
```

```
json
```

#### Return error example ：

#### 8. 2. 1 Description

#### Query CAN bus fault code information reported by OBD device.

#### 8. 2. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.obd.fault

### 8. 2 Get the OBD fault data of devices

```
"vin": "LC0CG4CF1H0029191"
},
{
"imei": "158511020000028",
"dataReportTime": "2024-05-09 1:27:23",
"odometerReading": "2048.6",
"deviceAccumulatedMileage": "143.9",
"remainingFuel": null,
"remainingFuelPercentage": "56",
"coolantTemperature": "78",
"vehicleBatterVoltage": "118",
"currentRPM": "5016",
"currentSpeed": "95.5",
"vin": "LC0CG4CF1H0029191"
}
]
}
}
```

##### {

```
"code": 1100 ,
"message": "Business exception ",
"result": null,
"data": null
}
```

```
json
```

```
 
```

#### 8. 2. 3 HTTP Request method

#### POST

#### 8. 2. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes - accesstoken: used for identifying legal th
```

```
account string Yes - The account to which the new fence belo
```

```
imeis string Yes -
```

```
Device imei(s), separate by comma. No m
time.Supports querying the data of the ac
account devices.Example
values:869247060001770,86924706000
```

```
start_time string Yes - Start time Format: yyyy-MM-dd HH:mm:s
```

```
end_time string Yes -
```

```
End time Format: yyyy-MM-dd HH:mm:ss
earlier than current time.
Query up to 31 days of data at a time.
```

```
page_no int Yes
```

##### >= 1

```
default:1
Page number
```

```
page_size int Yes
```

##### 1-100

```
default:10
Recordsin one page.
```

#### 8. 2. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
data object The returned data.
```

#### data list ：

```
key Type Description
```

```
currentPage int The current page, same as page_no.
```

```
pageSize int Recordsin one page, same as page_size.
```

```
totalRecord int Total rows
```

```
result array[object] Data details
```

#### result list ：

```
key Type Description
```

```
imei string Imei of device
```

```
deviceName string Device name
```

```
faultCode string The fault code of the fault
```

```
faultDetail string Fault detailed description
```

```
eventTime string Time when the fault was reported
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": {
"currentPage": 1 ,
"pageSize": 4 ,
"startRow": 0 ,
"endRow": 0 ,
"totalRecord": 6 ,
"totalPage": 0 ,
"result": [
{
"imei": "202509999999994",
"deviceName": "VL502_E-99994",
```

```
json
```

#### Return error example ：

## 9. Reports

```
"faultCode": "P1502",
"faultDetail": "High speed state of the vehicle - the front axle is
"eventTime": "2024-07-03 08:16:49"
},
{
"imei": "202509999999994",
"deviceName": "VL502_E-99994",
"faultCode": "B0074",
"faultDetail": "2nd row center seat belt pretensioner - start contro
"eventTime": "2024-07-03 08:26:49"
},
{
"imei": "202509999999994",
"deviceName": "VL502_E-99994",
"faultCode": "P2407",
"faultDetail": "Fuel evaporative emission system leak detection pump
"eventTime": "2024-07-03 08:36:49"
},
{
"imei": "202509999999994",
"deviceName": "VL502_E-99994",
"faultCode": "U0464",
"faultDetail": "Invalid data received from the navigation control mo
"eventTime": "2024-07-03 08:46:49"
}
]
}
}
```

##### {

```
"code": 1100 ,
"message": "Business exception ",
"result": null,
"data": null
}
```

```
json
```

```
 
```

#### 9. 1. 1 Description

#### Query the report data of the device's entry and exit fence.

#### 9. 1. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.platform.fence.duration

#### 9. 1. 3 HTTP Request method

#### POST

#### 9. 1. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes - accesstoken: used for identifying legal third
```

```
account string Yes - The account to which the new fence belong
```

```
imeis string Yes -
Example values:
869247060001770,869247060001259,869
```

```
start_time string Yes - Start time Format: yyyy-MM-dd HH:mm:ss
```

```
end_time string Yes -
End time Format: yyyy-MM-dd HH:mm:ss
end_time should be earlier than current tim
```

```
start_row string Yes - Row number of result set.
```

```
page_size string Yes - Recordsin one page.
```

#### 9. 1. 5 Response

```
key Type Description
```

```
code int Return code:
0: return correctly
```

### 9. 1 Get entry and exit fence data of devices

```
key Type Description
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
data object The returned data.
```

#### data list ：

```
key Type Description
```

```
totalTime string Request processing time
```

```
dataTotalRows string Total rows
```

```
rows array[object] Data details
```

#### rows list ：

```
key Type Description
```

```
imei string Imei of device
```

```
deviceName string Device name
```

```
fenceName string Fence name
```

```
enterTime string Enter fence time
```

```
exitTime number Leave fence time
```

```
duration number Duration
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": {
"totalTime": "0",
"dataTotalRows": "2",
```

```
json
```

#### Return error example ：

#### 9. 2. 1 Description

#### Query the trips report data of devices.

#### 9. 2. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.platform.report.trips

### 9. 2 Get the trips report data of devices

```
"rows": [
{
"imei": "869247060001739",
"deviceName": "JC450Pro-01739",
"fenceName": "geofence1",
"enterTime": "2022-12-10T01:05:33.000Z",
"exitTime": "2022-12-10T04:17:48.000Z",
"duration": "11535"
},
{
"imei": "869247060001739",
"deviceName": "JC450Pro-01739",
"fenceName": "geofence1",
"enterTime": "2022-12-10T01:05:33.000Z"
}
]
}
}
```

##### {

```
"code": 1100 ,
"message": "Business exception ",
"result": null,
"data": null
}
```

```
json
```

```
 
```

#### 9. 2. 3 HTTP Request method

#### POST

#### 9. 2. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes - accesstoken: used for identifying legal third
```

```
account string Yes - The account to which the new fence belong
```

```
imeis string Yes -
Example values:
869247060001770,869247060001259,869
```

```
type string Yes -
```

```
day/list
When type is day, the device itinerary data
by day.
When it is list, the detailed data of the devic
summarized and returned.
```

```
start_time string Yes - Start time Format: yyyy-MM-dd HH:mm:ss
```

```
end_time string Yes -
End time Format: yyyy-MM-dd HH:mm:ss
end_time should be earlier than current tim
```

```
start_row string Yes - Row number of result set.
```

```
page_size string Yes - Recordsin one page.
```

#### 9. 2. 5 Response

```
key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
data object The returned data.
```

#### data list ：

```
key Type Description
```

```
dayList array[object] When type = list, the array is returned
```

```
datDatas array[object] When type = day, the array is returned
```

#### datDatas list:

```
key Type Description
```

```
deviceName string Device Name
```

```
deviceImei string Device imei
```

```
data object
```

#### datDatas.data:

```
key Type Description
```

```
date string Date
```

```
totalTrips string Total number of trips on the day
```

```
averageSpeed string Average speed
```

```
fuel string Fuel consumption
```

```
maxSpeed string Maximum speed
```

```
oilWear string Fuel consumption per 100 kilometers
```

```
totalMileage string Total mileage
```

```
travelTime string Total trip time
```

#### dayList list ：

```
key Type Description
```

```
imei string Imei of device
```

```
deviceName string Device name
```

```
key Type Description
```

```
tripsData Array[object] Trip information, see the table below for details
```

```
inTotal object
```

```
Trip information summary:
allTrips: total number of trips
totalDis: total mileage
travelTime: total running time
totalTime: formatted total running time
totalAvgSpeed: average speed
allTotalMaxSpeed: maximum average speed
totalFuel: total fuel consumption
oilWear: fuel consumption per 100 kilometers
```

#### dayList.tripsData ：

```
key Type Description
```

```
Searchdate string date
```

```
tripNum number Number of trips on the day
```

```
inTotal object
```

```
Data summary for the day :
totalDis: total mileage
travelTime: total running time
totalTime: formatted total running time
totalAvgSpeed: average speed
allTotalMaxSpeed: maximum average speed
totalFuel: total fuel consumption
oilWear: fuel consumption per 100 kilometers
```

```
dayData Array[object]
For each itinerary information, please see the table below for
detailed information
```

#### dayList.tripsData.dayData:

```
key Type Description
```

```
imei string Device imei
```

```
startTime string Start time of the trip
```

```
endTime string End time of the trip
```

```
key Type Description
```

```
startLat string Start latitude of the trip
```

```
startLng string Start longitude of the trip
```

```
endLat string End latitude of the trip
```

```
endLng string End longitude of the trip
```

```
totalMileage string Mileage of the trip
```

```
travelTime string Time
```

```
averageSpeed string Average speed
```

```
maxSpeed string Maximum speed
```

```
oilWear string Fuel consumption per 100 kilometers
```

```
fuel string Fuel consumption
```

```
startMileage string Start mileage
```

```
endMileage string End mileage
```

#### Correct return example ：

#### Return error example ：

### 9. 3 Get device alarm list

##### {

```
"code": 1100 ,
"message": "Business exception ",
"result": null,
"data": null
}
```

```
json
```

```
json
```

#### 9. 3. 1 Description

#### Get device alarm list. Time range of searching criteria should be within 1 month. Return

#### maximum 1000 rows.

#### 9. 3. 2 Request URL

#### See the unique request URL.

#### Method = jimi.device.alarm.list

#### 9. 3. 3 HTTP Request method

#### POST

#### 9. 3. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes
accesstoken: used for identifying
legal third party
```

```
imei string Yes -
Device imei: This field can be used
when querying a single device.
```

```
alertTypeId string No
If not specify alertTypeId, all alert
type will return.
```

```
begin_time date No
```

```
if not provide begin_time &
end_time, returns latest 50 alerts of
last 1 month.
```

```
end_time date No
```

```
imeis string Yes
```

```
Device imei(s), separate by
comma.Query single or multiple
devices No more than 100 each
time. Select one of the IMEI and
IMEIS fields to fill in.
```

```
page_no int Yes
```

##### >= 1

```
default:1
Page number
```

```
Parameter Type Required Remark Description
```

```
page_size int Yes
```

##### 1-100

```
default:50
Recordsin one page.
```

#### 9. 3. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned data
```

#### Result list ：

```
Key Type Description
```

```
deviceName string Device Name
```

```
imei string IMEI
```

```
model string Device Model
```

```
account string Account
```

```
alertTypeId string Alert type ID
```

```
alertTypeName string Alert type name
```

```
alertTime string Alert time
```

```
positioningTime string Time of alert positioning
```

```
lng double longitude
```

```
lat double latitude
```

```
speed string speed
```

```
geoid string Fence ID
```

#### Correct return example ：

#### Return error example ：

#### 9. 4. 1 Description

#### Query the RFID information reported within a certain period of time.

#### 9. 4. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.device.rfid.list

### 9. 4 Get RFID reporting information

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"deviceName": "ABC-34352",
"imei": "343503422910345",
"model": "GT06N",
"account": "test1234",
"alertTypeId": "1002",
"alarmTypeName": "ACC On",
"alertTime": "2019-03-14 14:02:03",
"positioningTime": "2019-03-14 14:02:03",
"lat": 22.577144898887813,
"lng": 113.91674845964586,
"speed": "10",
"geoid": "se8yg081p0qh5vnniqrakr1nr0tdh6a0"
}
]
}
```

##### {

```
"code":xxx,
"message": "Illegal device"
}
```

```
json
```

```
json
```

#### 9. 4. 3 HTTP Request method

#### POST

#### 9. 4. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying
legal third party
```

```
account string Yes -
```

```
The account to which the device
belongs. If the device to be queried
belongs to multiple different users,
please enter the common superior
account of these users here.
```

```
imeis string No -
```

```
Device imei(s), separate by
comma.No more than 100 each
time If neither imei nor card id is
entered, all RFID reporting records
within the query time period will be
queried.
```

```
card_ids string No -
```

```
RFID(s),separate by comma.No
more than 100 each time If neither
imei nor card id is entered, all RFID
reporting records within the query
time period will be queried.
```

```
begin_time number Yes -
Start time Format: yyyy-MM-dd
HH:mm:ss
```

```
end_time number Yes -
```

```
End time Format: yyyy-MM-dd
HH:mm:ss end_time should be
earlier than current time Query up
to 1 month of data at a time
```

```
page_no int no
```

##### >= 1

```
default:1
Page number
```

```
Parameter Type Required Remark Description
```

```
page_size int no
```

##### 1-100

```
default:10
Records in one page.
```

#### 9. 4. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
data object The returned parameters
```

#### data object ：

```
Key Type Description
```

```
currentPage number Current page code
```

```
pageSize number Records in one page.
```

```
totalRecord number Total amount of data that meets the conditions
```

```
result Array[Object] The returned data
```

#### result list ：

```
Key Type Description
```

```
cardId string The corresponding RFID in the reporting record
```

```
imei string RFID record reporting corresponding imei
```

```
photo string Photo taken by the device during RFID reporting
```

```
operationTime string RFID record reporting time
```

#### Correct return example ：

#### Return error example ：

#### 9. 5. 1 Description

#### Retrieve health data.

#### 9. 5. 2 Request URL

### 9. 5 Retrieve Health Data

##### {

```
"code": 0 ,
"message": "success",
"data": {
"currentPage": 1 ,
"pageSize": 10 ,
"startRow": 1 ,
"endRow": 10 ,
"totalRecord": 34 ,
"totalPage": 4 ,
"result": [
{
"imei": "890768902346789",
"cardId": "278907",
"operationTime": "2024-04-22 09:12:23",
"photo":
}
]
}
}
```

##### {

```
"code":"xxx",
"message": "no permissions"
}
```

```
json
```

```
json
```

#### See the unique request URL.

#### Method = jimi.device.health.data

#### 9. 5. 3 HTTP Request method

#### POST

#### 9. 5. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

```
imei string Yes - Device imei
```

```
query_type String Yes
```

```
day: query today's data;
week: query data within a week;
month: query data within a month
```

#### 9. 5. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string
```

```
data object The returned parameters
```

#### data object

```
Key Type Description
```

```
currentSteps int Last reported steps
```

```
Key Type Description
```

```
currentBloodOxygen int Last reported blood oxygen
```

```
currentHearRate int Last reported heart rate
```

```
historyHealthVOList array[object] Health report data
```

#### historyHealthVOList list

```
Key Type Description
```

```
avgBloodOxygen int Average blood oxygen
```

```
avgHeartRate int Average heart rate
```

```
postTime string Statistical time
```

```
step int Steps
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": {
"currentSteps": 327 ,
"currentBloodOxygen": 99 ,
"currentHeartRate": 70 ,
"historyHealthVOList": [
{
"avgBloodOxygen": 0 ,
"avgHeartRate": 0 ,
"postTime": "2025-11-04 00:00:00",
"step": 0
},
{
"avgBloodOxygen": 97 ,
"avgHeartRate": 91 ,
"postTime": "2025-11-04 01:00:00",
"step": 0
},
{
```

```
json
```

#### Return error example ：

#### 9. 6. 1 Description

#### Retrieve DLT report information.

#### 9. 6. 2 Request URL

#### See the unique request URL.

#### Method = jimi.open.platform.dlt.report

#### 9. 6. 3 HTTP Request method

#### POST

### 9. 6 Retrieve DLT Report Information

```
"avgBloodOxygen": 98 ,
"avgHeartRate": 95 ,
"postTime": "2025-11-04 02:00:00",
"step": 0
},
{
"avgBloodOxygen": 98 ,
"avgHeartRate": 84 ,
"postTime": "2025-11-04 03:00:00",
"step": 50
},
{
"avgBloodOxygen": 98 ,
"avgHeartRate": 70 ,
"postTime": "2025-11-04 06:00:00",
"step": 327
}
],
"healthPostTime": "2025-11-04 06:28:55"
}
}
```

```
json
```

#### 9. 6. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
accesstoken: used for identifying legal
third party
```

```
start_time string Yes - Start time yyyy-MM-dd HH:mm:ss
```

```
end_time string Yes - End time yyyy-MM-dd HH:mm:ss
```

```
imei string No - Device imei
```

```
dlt string No DLT card number
```

#### 9. 6. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
1100:Business exception
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string
```

```
data array[object] The returned parameters
```

#### data list

```
Key Type Description
```

```
deviceName string Device name
```

```
deviceStatus int Visa status: 1 for check-in, 2 for check-out
```

```
driverNo string Driver number
```

```
driverName string Driver name
```

```
Key Type Description
```

```
numberPlate string License plate number
```

```
status int Card swiping status: 1 for alarm, 0 for normal
```

```
operationTime string Operation time (UTC time zone)
```

```
coordinates string Longitude and latitude
```

```
imei string Device IMEI
```

```
dlt string DLT card number
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "success",
"result": null,
"data": [
{
"deviceName": "JC371-00643",
"deviceStatus": 1 ,
"driverNo": null,
"driverName": null,
"numberPlate": null,
"status": 0 ,
"operationTime": "2025-10-27 10:43:07",
"coordinates": "113.943096,22.576624",
"imei": "865478070000643",
"dlt": "232000455700107"
},
{
"deviceName": "JC371-00643",
"deviceStatus": 2 ,
"driverNo": null,
"driverName": null,
"numberPlate": null,
"status": 0 ,
"operationTime": "2025-10-27 10:42:09",
"coordinates": "113.943108,22.576613",
"imei": "865478070000643",
```

```
json
```

#### Return error example ：

#### 10. 1. 1 Description

#### List all sub-account of a specified account.

#### 10. 1. 2 Request URL

#### See the unique request URL.

#### Method = jimi.user.child.list

#### 10. 1. 3 HTTP Request method

#### POST

#### 10. 1. 4 Request parameters

## 10. Account Management

### 10. 1 List all sub-account

```
"dlt": "232000455700107"
},
{
"deviceName": "JC371-00643",
"deviceStatus": 1 ,
"driverNo": null,
"driverName": null,
"numberPlate": null,
"status": 0 ,
"operationTime": "2025-10-27 10:41:07",
"coordinates": "113.943113,22.576603",
"imei": "865478070000643",
"dlt": "232000455700107"
}
]
}
```

```
json
```

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
Access token for security access JIMI
Server.
```

```
target string Yes - The specified account for inquired.
```

#### 10. 1. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result Array[Object] The returned parameters
```

#### result

```
key Type Description
```

```
account string log in account
```

```
name string name
```

```
type int
```

```
Account Type
3 ：App user
8 ：Distributor
9 ：ordinary users
10 ：ordinary distributor
11 ：sales
```

```
displayFlag int Available or not (1:Available,0:not available)
```

```
address string location
```

```
birth string birthday
```

```
companyName string Company Name
```

```
key Type Description
```

```
email string mailbox
```

```
phone string contact number
```

```
language string Language (zh,en)
```

```
sex int Gender 0 male， 1 female
```

```
enabledFlag int Flag:1 Available, 0not available
```

```
remark string Remark
```

#### Correct return example ：

#### Return error example ：

##### {

```
"code": 0 ,
"message": "success",
"result": [
{
"account": "123123",
"name": "test",
"type": 8 ,
"displayFlag": 1 ,
"address": null,
"birth": "2017-04-22 00:00:00",
"companyName": "",
"email": "",
"phone": "",
"language": "zh",
"sex": 0 ,
"enabledFlag": 1 ,
"remark": null
}
]
}
```

##### {

```
"code": "xxx",
```

```
json
```

```
json
```

#### 10. 2. 1 Description

#### Create a sub-account of a specified account.

#### 10. 2. 2 Request URL

#### See the unique request URL.

#### Method = jimi.user.child.create

#### 10. 2. 3 HTTP Request method

#### POST

#### 10. 2. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
Access token for security access
JIMI Server.
```

```
super_account string No -
The specified parent account. If not
provided, use API account instead.
```

```
account_id string Yes
```

```
Account id, length of 3 ~ 30
characters. Support special
characters like “_@.” as well as a - Z,
0 - 9.
```

```
nick_name string Yes Nick name or customer name
```

```
account_type int Yes 1 - Distributor 2 - End User 3 - Sales
```

```
password string Yes Password with md5.
```

### 10. 2 Create sub-account

```
"message": "The account does not exist"
}
```

```
Parameter Type Required Remark Description
```

```
telephone string No
```

```
Email string Yes
User could retrieve password if forgot
it.
```

```
contact_person string No
```

```
company_name string No
```

```
permissions string Yes
```

```
6 permissions can be set:
Web Login
App Login
Send Command
Set Working Mode
Edit by Web
Edit by App
0 - disable, 1 - enable, 6 digital
represents 6 kinds of permissions
enable/disable perssion. For example,
111000
```

#### 10. 2. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned parameters
```

#### Correct return example ：

##### {

```
"code": 0 ,
"message": "Vehicle information modification successful",
"result": null
}
```

```
json
```

#### Return error example ：

#### 10. 3. 1 Description

#### Move account from one sub-account to another sub-account.

#### 10. 3. 2 Request URL

#### See the unique request URL.

#### Method = jimi.user.child.move

#### 10. 3. 3 HTTP Request method

#### POST

#### 10. 3. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
Access token for security access JIMI
Server.
```

```
account string Yes - The account will be moved.
```

```
target_account string Yes The target account to move in.
```

#### 10. 3. 5 Response

### 10. 3 Move account

##### {

```
"code": "xxx",
"message": "The account does not exist"
}
```

```
json
```

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned parameters
```

#### Correct return example ：

#### Return error example ：

#### 10. 4. 1 Description

#### Remove a sub-account of a specified account.

#### 10. 4. 2 Request URL

#### See the unique request URL.

#### Method = jimi.user.child.del

#### 10. 4. 3 HTTP Request method

#### POST

### 10. 4 Remove sub-account

##### {

```
"code": 0 ,
"message": "Vehicle information modification successful",
"result": null
}
```

##### {

```
"code": "xxx",
"message": "no permissions"
}
```

```
json
```

```
json
```

#### 10. 4. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
Access token for security access JIMI
Server.
```

```
super_account string No -
The specified parent account. If not
provided, use API account instead.
```

```
account_id string Yes
```

```
Account id, length of 3 ~ 30 characters.
Support special characters like “_@.”
as well as a - Z, 0 - 9.
```

#### 10. 4. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned parameters
```

#### Correct return example ：

#### Return error example ：

##### {

```
"code": 0 ,
"message": "Vehicle information modification successful",
"result": null
}
```

##### {

```
"code": "xxx",
```

```
json
```

```
json
```

#### 10. 5. 1 Description

#### Edit the user information of the platform, only edit the user's nickname, mobile phone

#### number, email address, contact person, company name and permissions.

#### 10. 5. 2 Request URL

#### See the unique request URL.

#### Method = jimi.user.child.update

#### 10. 5. 3 HTTP Request method

#### POST

#### 10. 5. 4 Request parameters

#### ( 1 )Common parameters

#### ( 2 )Private parameters as follow:

```
Parameter Type Required Remark Description
```

```
access_token string Yes -
Access token for security access
JIMI Server.
```

```
edit_account String Yes
```

```
Account name, length of 3 ~ 30
characters. Support special
characters like “_@.” as well as a-Z,
0-9.
```

```
nick_name String Yes Nick name or customer name
```

```
telephone String No
```

```
Email String Yes
User could retrieve password if
forgot it.
```

```
contact_person String No contact
```

### 10. 5 Edit user information

```
"message": "no permissions"
}
```

```
Parameter Type Required Remark Description
```

```
company_name String No Company name
```

```
permissions String Yes
```

```
Web Login App Login Send
Command Set Working Mode Edit by
Web Edit by App
6 digital represents enable/disable
perssion.
```

#### 10. 5. 5 Response

```
Key Type Description
```

```
code int
```

```
Return code:
0: return correctly
Other: failure. Refer to the error code description
```

```
message string If code is not 0, there will be a corresponding error message
```

```
result string The returned data,could be multiple rows.
```

#### Remark: Unix timestamp; Example: 1611105520 = 2021 - 01 - 20 09 : 18 : 40 \* Correct return

#### example ：

#### Return error example ：

## 11 .Appendix

### Device alarm type

##### {

```
"code": 0 ,
"message": "Account update success!",
"result": null,
"data": null
}
```

```
json
```

```
json
```

#### The alertTypeId of the alarm type actively reported by the device is a number.

#### The alertTypeId of the alarm generated after platform logic judgment is English text.

**alertTypeId alertType**

1 SOS alert

10 Enter GPS blind zone alert

100 Cancel Notification for Temperature Alert

101 Cancel Notification for Collision Alert

11 Exit GPS blind zone alert

113 Increase in oil level

114 Install alert

115 Oil Sence Timeout

119 High voltage at ADC1

12 Booting notification

120 Low voltage at ADC1

126 High humidity

127 Low humidity

128 DVR vibration alert

13 GPS first fix notification

135 Overspeed alert(DVR)

136 Power off alert(DVR)

138 Immobilization ON

139 Immobilization OFF

14 Low external power alert

140 Close eyes Alert

141 Switch Land Transport Mode Alarm

**alertTypeId alertType**

142 Environmental Anomaly Alarm

143 Distraction Alert

144 Sudden Acceleration Alert(DVR)

145 Sudden Deceleration Alert(DVR)

146 Sharp Turn Alert(DVR)

147 Collision Alert(DVR)

148 No Face Alert

149 Switch Ocean Transport Mode Alarm

15 Low power protection alert

150 Switch Static Transport Mode Alarm

151 Phone Calling Alert

154 Smoking Alert

16 Sim card change alert

160 Yawn Alert

163 Head lowered

165 RFID reporting event

168 Engine failure

169 Undervoltage

17 Power off alert

170 Drinking

171 Light detected alert

172 Bluetooth MAC searched

173 Bluetooth MAC lost

18 Airplane mode after low power protection

**alertTypeId alertType**

19 Disassembly alert

191 Device Plug-out Alert

197 Engine ON

198 Engine OFF

199 Overtime driving alert

2 Power cut off alert

20 Door detection alert

202 overspeed warning

203 Overtime parking warning

204 Forward collision warning

205 Lane departure warning

206 Vehicle too close warning

207 pedestrian collision warning

208 DMS fatigue warning

21 Battery low power shutdown

22 Voice alarm

224 Device Plug-in Alert

227 Overheating

230 INPUT1 was activated

231 INPUT1 was deactivated

232 INPUT2 was activated

233 INPUT2 was deactivated

24 Cover Move Alert

25 Internal low battery alert

**alertTypeId alertType**

254 Ignition on

256 Fence entry alarm (Bluetooth)

257 Exit fence alarm (Bluetooth)

258 Fence entry alarm (WIFI)

259 Exit fence alarm (WIFI)

260 long periods of stillness

261 Start exercise reminder

262 Stop exercise reminder

263 LTE Jamming Detected

266 LTE Jamming Ended

267 GPS Jamming Detected

268 GPS Jamming Ended

28 Door open alert

29 Door close alert

3 Vibration alert

35 Fall Alert

36 Plug in charger

39 Unauthorized Open Alert

4 Enter geo-fence(terminal)

40 Initiative Offline(Power Off) Alert

41 Sudden Acceleration Alert

42 Sharp Turn Left Alert

43 Sharp Turn Right Alert

44 Collision Alert

**alertTypeId alertType**

45 Vehicle Turn Over Alarm

48 Sudden Deceleration Alert

5 Exit geo-fence(terminal)

50 Device Pull Out Alarm

55 Collision Alert

58 Cancel Notification for Unauthorized Open Alert

6 Overspeed alert(terminal)

71 Fatigue driving alert

76 Sharp turn alarm

77 Abrupt lane switching alarm

78 Vehicle stability

79 Vehicle angle abnormality

80 Door close alert

81 Door open alert

82 Temperature Alert

83 Stealing oil alarm

86 Start charging

87 Stop charging/remove charger

89 full of reminders

9 Displacement alert(terminal)

90 Low battery alert

91 High Temperature Alert(terminal)

92 Low Temperature Alert(terminal)

ACC_OFF ACC OFF

**alertTypeId alertType**

ACC_ON ACC ON

burglarStatus\_ 0 Disarm

burglarStatus\_ 1 Arm

burglarStatus\_ 2 Alert

carFault Vehicle fault alert

displacementAlarm Night Driving Alert

DMSAlert DMS Alert

drivingBehaviorAlert Driving Behavior Alert

drivingBehaviorAlertDVR Driving Behavior Alert(DVR)

fenceOverspeed Fence Overspeed alert

geozone Geo-fence alert

high_temp_alarm High Temperature Alert(platform)

in Enter geo-fence

laneshift Route Deviation Alert

low_temp_alarm Low Temperature Alert(platform)

mileageAlarm Maintenance alert

obd OBD alert

offline Offline alert

other Other alerts

out Exit geo-fence

overSpeed Overspeed alert(platform)

sensitiveAreasFence Sensitive areas fence

statusLeftFrontDoors\_ 0 Left front door close

statusLeftFrontDoors\_ 1 Left front door open

**alertTypeId alertType**

statusLeftFrontWindows\_ 0 Left front window close

statusLeftFrontWindows\_ 1 Left front window open

statusLeftRearWindows\_ 0 Left rear door close

statusLeftRearWindows\_ 1 Left rear door open

statusRightFrontDoors\_ 0 Right front door close

statusRightFrontDoors\_ 1 Right front door open

statusRightFrontWindows\_ 0 Right front window close

statusRightFrontWindows\_ 1 Right front window open

statusRightRearDoors\_ 0 Right rear door close

statusRightRearDoors\_ 1 Right rear door open

statusRightRearWindows\_ 0 Right rear window close

statusRightRearWindows\_ 1 Right rear window open

statusTrunk\_ 0 Trunk close

statusTrunk\_ 1 Trunk open

stayAlert Parking alert

stayAlertOn Idling alert

stayTimeIn Long time not enter the Geo-fence

stayTimeOut Long time not exit the Geo-fence

ubiAcce Harsh acceleration

ubiColl Collision

ubiDece Harsh braking

ubiLane Sudden lane change

ubiRoll Rollover

ubiSatt Roll and pitch

**alertTypeId alertType**

ubiStab Skidding

ubiTurn Harsh cornering

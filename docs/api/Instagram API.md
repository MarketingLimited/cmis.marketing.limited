IG User Media  
Represents a collection of IG Media objects on an IG User.

On July 9, 2025, we added support for the existing user\_tags field for image and video stories on the /\<IG\_ID\>/media endpoint. You can mention users in a story and optionally specify x, y coordinates to tag them at a particular coordinate in the media.

On March 24, 2025, we introduced the new alt\_text field for image posts on the /\<INSTAGRAM\_PROFESSIONAL\_ACCOUNT\_ID\>/media endpoint. Reels and stories are not supported.

Creating  
POST /\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media

Create an image, carousel, story or reel IG Container for use in the post publishing process. See the Content Publishing guide for complete publishing steps.  
Steps to publish a media object include the following:

Create a container  
Upload the media to the container  
Publish the container  
Limitations  
General Limitations  
Containers expire after 24 hours  
An Instagram account can only create 400 containers within a rolling 24 hour period  
If the Page connected to the targeted Instagram professional account requires Page Publishing Authorization (PPA), PPA must be completed or the request will fail  
If the Page connected to the targeted Instagram professional account requires two-factor authentication, the Facebook User must also have performed two-factor authentication or the request will fail  
We strongly recommended the HTTP IETF standard character set for URLs, URLs that contain only US ASCII characters, or the request will fail  
Reels Limitations  
Reels cannot appear in carousels  
Account privacy settings are respected upon publish. For example, if Allow remixing is enabled, published reels will have remixing enabled upon publish but remixing can be disabled on published reels manually through the Instagram app.  
Music tagging is only available for original audio.  
Story Limitations  
Stories expire after 24 hours.  
Support either video URL or Reels URL but not both.  
Publishing stickers (i.e., link, poll, location) is not supported; however mentioning users without a sticker is supported.  
Requirements  
Type	Description  
Access Tokens

User

Business Roles

If creating containers for product tagging, the app user must have an admin role on the Business Manager that owns the IG User's Instagram Shop.

Permissions

instagram\_basic  
instagram\_content\_publish  
pages\_read\_engagement If the app user was granted a role on the Page via the Business Manager, you will also need one of:

ads\_management  
ads\_read

If creating containers for product tagging, you will also need:

catalog\_management  
instagram\_shopping\_tag\_products

Tasks

Your app user must be able to perform the MANAGE or CREATE\_CONTENT tasks on the Page linked to their Instagram professional account.

Image Specifications  
Format: JPEG  
File size: 8 MB maximum.  
Aspect ratio: Must be within a 4:5 to 1.91:1 range  
Minimum width: 320 (will be scaled up to the minimum if necessary)  
Maximum width: 1440 (will be scaled down to the maximum if necessary)  
Height: Varies, depending on width and aspect ratio  
Color Space: sRGB. Images using other color spaces will have their color spaces converted to sRGB.  
Reel Specifications  
The following are the specifications for Reels:

Container: MOV or MP4 (MPEG-4 Part 14), no edit lists, moov atom at the front of the file.  
Audio codec: AAC, 48khz sample rate maximum, 1 or 2 channels (mono or stereo).  
Video codec: HEVC or H264, progressive scan, closed GOP, 4:2:0 chroma subsampling.  
Frame rate: 23-60 FPS.  
Picture size:  
Maximum columns (horizontal pixels): 1920  
Required aspect ratio is between 0.01:1 and 10:1 but we recommend 9:16 to avoid cropping or blank space.  
Video bitrate: VBR, 25Mbps maximum  
Audio bitrate: 128kbps  
Duration: 15 mins maximum, 3 seconds minimum  
File size: 300MB maximum  
The following are the specifications for a Reels cover photo:

Format: JPEG  
File size: 8MB maximum  
Color Space: sRGB. Images that use other color spaces will be converted to sRGB.  
Aspect ratio: We recommend 9:16 to avoid cropping or blank space. If the aspect ratio of the original image is not 9:16, we crop the image and use the middle most 9:16 rectangle as the cover photo for the reel. If you share a reel to your feed, we crop the image and use the middle most 1:1 square as the cover photo for your feed post.  
Story Image Specifications  
Format: JPEG  
File size: 8 MB maximum.  
Aspect ratio: We recommended 9:16 to avoid cropping or blank space  
Color Space: sRGB. Images using other color spaces will have their color spaces converted to sRGB  
Story Video Specifications  
Container: MOV or MP4 (MPEG-4 Part 14), no edit lists, moov atom at the front of the file.  
Audio codec: AAC, 48khz sample rate maximum, 1 or 2 channels (mono or stereo).  
Video codec: HEVC or H264, progressive scan, closed GOP, 4:2:0 chroma subsampling.  
Frame rate: 23-60 FPS.  
Picture size:  
Maximum columns (horizontal pixels): 1920  
Required aspect ratio is between 0.1:1 and 10:1 but we recommend 9:16 to avoid cropping or blank space  
Video bitrate: VBR, 25Mbps maximum  
Audio bitrate: 128kbps  
Duration: 60 seconds maximum, 3 seconds minimum  
File size: 100MB maximum  
Request Syntax  
Image Containers  
POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_IG\_USER\_ID\>/media  
?image\_url=\<IMAGE\_URL\>  
\&is\_carousel\_item=\<TRUE\_OR\_FALSE\>  
\&alt\_text=\<IMAGE\_ALTERNATIVE\_TEXT\>        
\&caption=\<IMAGE\_CAPTION\>  
\&location\_id=\<LOCATION\_PAGE\_ID\>  
\&user\_tags=\<ARRAY\_OF\_USERS\_FOR\_TAGGING\>\>  
\&product\_tags=\<ARRAY\_OF\_PRODUCTS\_FOR\_TAGGING\>  
\&access\_token=\<USER\_ACCESS\_TOKEN\>  
Reel Containers  
Standard upload  
POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?media\_type=REELS  
\&video\_url=\<REEL\_URL\>  
\&caption=\<IMAGE\_CAPTION\>  
\&share\_to\_feed=\<TRUE\_OR\_FALSE\>  
\&collaborators=\<COLLABORATOR\_USERNAMES\>  
\&cover\_url=\<COVER\_URL\>  
\&audio\_name=\<AUDIO\_NAME\>  
\&user\_tags=\<ARRAY\_OF\_USERS\_FOR\_TAGGING\>\>  
\&location\_id=\<LOCATION\_PAGE\_ID\>  
\&thumb\_offset=\<THUMB\_OFFSET\>  
\&share\_to\_feed=\<TRUE\_OR\_FALSE\>  
\&access\_token=\<USER\_ACCESS\_TOKEN\>  
Resumable upload session  
POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?media\_type=REELS  
\&upload\_type=resumable  
\&caption=\<IMAGE\_CAPTION\>  
\&collaborators=\<COLLABORATOR\_USERNAMES\>  
\&cover\_url=\<COVER\_URL\>  
\&audio\_name=\<AUDIO\_NAME\>  
\&user\_tags=\<ARRAY\_OF\_USERS\_FOR\_TAGGING\>\>  
\&location\_id=\<LOCATION\_PAGE\_ID\>  
\&thumb\_offset=\<THUMB\_OFFSET\>  
\&access\_token=\<USER\_ACCESS\_TOKEN\>  
On success, an ig-container-id and a uri is returned in the response, which will be used in subsequent steps, such as:

{  
  "id": "\<IG\_CONTAINER\_ID\>",  
  "uri": "https://rupload.facebook.com/ig-api-upload/v24.0/\<IG\_CONTAINER\_ID\>"  
}  
Carousel Containers  
Carousel containers only. To create carousel item containers, create image or video containers instead (reels are not supported). See Carousel Posts for complete publishing steps.

Standard upload  
POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?media\_type=CAROUSEL  
\&caption=\<IMAGE\_CAPTION\>  
\&share\_to\_feed=\<TRUE\_OR\_FALSE\>  
\&collaborators=\<COLLABORATOR\_USERNAMES\>  
\&location\_id=\<LOCATION\_PAGE\_ID\>  
\&product\_tags=\<ARRAY\_OF\_PRODUCTS\_FOR\_TAGGING\>  
\&children=\<ARRAY\_OF\_CAROUSEL\_CONTAINTER\_IDS\>  
\&access\_token=\<USER\_ACCESS\_TOKEN\>  
Resumable upload session  
POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?media\_type=VIDEO  
\&is\_carousel\_item=true  
\&upload\_type=resumable  
\&access\_token=\<USER\_ACCESS\_TOKEN\>  
On success, an ig-container-id and a uri is returned in the response, which will be used in subsequent steps.

Image Story Containers  
POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?image\_url=\<IMAGE\_URL\>  
\&media\_type=STORIES  
\&user\_tags=\<ARRAY\_OF\_USERS\_FOR\_TAGGING\>  
\&access\_token=\<USER\_ACCESS\_TOKEN\>  
Video Story Containers  
Standard upload  
POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?video\_url=\<VIDEO\_URL\>  
\&media\_type=STORIES  
\&user\_tags=\<ARRAY\_OF\_USERS\_FOR\_TAGGING\>  
\&access\_token=\<USER\_ACCESS\_TOKEN\>  
Resumable upload session  
POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?media\_type=STORIES  
\&upload\_type=resumable  
\&access\_token=\<USER\_ACCESS\_TOKEN\>  
On success, an Instagram container ID and a URI is returned in the response, which will be used in subsequent steps.

Upload a video through resumable upload protocol  
Once the Instagram container ID returns from a resumable upload session call, send a POST request to the https://rupload.facebook.com/ig-api-upload/ v24.0/\<IG\_CONTAINER\_ID\> endpoint.  
All media\_type shares the same flow to upload the video.  
ig-container-id is the ID from the resumable reels, carousel and video container upload session examples above.  
access-token is the same one used in other steps.  
offset is set to the first byte being upload, generally 0\.  
file\_size is set to the size of your file in bytes.  
Your\_file\_local\_path sets to the file path of your local file, for example, if uploading a file from the Downloads folder on macOS, the path is @Downloads/example.mov.  
curl \-X POST "https://rupload.facebook.com/ig-api-upload/v24.0/\<IG\_CONTAINER\_ID\>" \\  
     \-H "Authorization: OAuth \<USER\_ACCESS\_TOKEN\>" \\  
     \-H "offset: 0" \\  
     \-H "file\_size: Your\_file\_size\_in\_bytes" \\  
     \--data-binary "@Your\_local\_file\_path.extension"  
On success, you should see response like this example:

{  
  "success":true,  
  "message":"Upload successful. ..."  
}    
Upload a video from a hosted URL  
This service can also support video upload from a hosted URL.

curl \-X POST "https://rupload.facebook.com/ig-api-upload/v24.0/\<IG\_CONTAINER\_ID\>" \\  
     \-H "Authorization: OAuth \<USER\_ACCESS\_TOKEN\>" \\  
     \-H "file\_url: \<VIDEO\_URL\>"  
Path Parameters  
Placeholder	Value  
\<LATEST\_API\_VERSION\>

The lastest API version is: v24.0	  
API version.

\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>  
Required

App user's app-scoped user ID.

Query String Parameters  
Key	Placeholder	Description  
access\_token

\<USER\_ACCESS\_TOKEN\>

Required. App user's User access token.

alt\_text

\<IMAGE\_ALTERNATIVE\_TEXT\>

For image posts only. Alternative text, up to 1000 character, for an image. Only supported on a single image or image media in a carousel.

Reels and stories are not supported.

audio\_name

\<AUDIO\_NAME\>

For Reels only. Name of the audio of your Reels media. You can only rename once, either while creating a reel or after from the audio page.

caption

\<IMAGE\_CAPTION\>

A caption for the image, video, or carousel. Can include hashtags (example: \#crazywildebeest) and usernames of Instagram users (example: @natgeo). @Mentioned Instagram users receive a notification when the container is published. Maximum 2200 characters, 30 hashtags, and 20 @ tags.

Not supported on images or videos in carousels.

collaborators

\<LIST\_OF\_COLLABORATORS\>

For Feed image, Reels and Carousels only. A list of up to 3 instagram usernames as collaborators on an ig media.

Not supported for Stories.

children

\<ARRAY\_OF\_CAROUSEL\_CONTAINTER\_IDS

Required for carousels. Applies only to carousels. An array of up to 10 container IDs of each image and video that should appear in the published carousel. Carousels can have up to 10 total images, vidoes, or a mix of the two.

cover\_url

\<COVER\_URL\>

For Reels only. The path to an image to use as the cover image for the Reels tab. We will cURL the image using the URL that you specify so the image must be on a public server. If you specify both cover\_url and thumb\_offset, we use cover\_url and ignore thumb\_offset. The image must conform to the specifications for a Reels cover photo.

image\_url

\<IMAGE\_URL\>

For images only and required for images. The path to the image. We will cURL the image using the URL that you specify so the image must be on a public server.

is\_carousel\_item

\<TRUE\_OR\_FALSE\>

Applies only to images and video. Set to true. Indicates image or video appears in a carousel.

location\_id

\<LOCATION\_PAGE\_ID\>

The ID of a Page associated with a location that you want to tag the image or video with.

Use the Pages Search API to search for Pages whose names match a search string, then parse the results to identify any Pages that have been created for a physical location. Include the location field in your query and verify that the Page you want to use has location data. Attempting to create a container using a Page that has no location data will fail with coded exception INSTAGRAM\_PLATFORM\_API\_\_INVALID\_LOCATION\_ID.

Not supported on images or videos in carousels.

media\_type

\<MEDIA\_TYPE\>

Required for carousels, stories, and reels. Indicates container is for a carousel, story or reel. Value can be:

CAROUSEL  
REELS  
STORIES  
product\_tags

\<ARRAY\_OF\_PRODUCTS\_FOR\_TAGGING\>

Required for product tagging. Applies only to images and videos. An array of objects specifying which product tags to tag the image or video with (maximum of 5; tags and product IDs must be unique). Each object should have the following information:

product\_id — Required. Product ID.  
x — Images only. An optional float that indicates percentage distance from left edge of the published media image. Value must be within 0.0–1.0 range.  
y — Images only. An optional float that indicates percentage distance from top edge of the published media image. Value must be within 0.0–1.0 range.  
For example:

\[{product\_id:'3231775643511089',x: 0.5,y: 0.8}\]

share\_to\_feed

\<TRUE\_OR\_FALSE\>

For Reels only. When true, indicates that the reel can appear in both the Feed and Reels tabs. When false, indicates the reel can only appear in the Reels tab.

Neither value determines whether the reel actually appears in the Reels tab because the reel may not meet eligibilty requirements or may not be selected by our algorithm. See reel specifications for eligibility critera.

thumb\_offset

\<THUMB\_OFFSET\>

For videos and reels. Location, in milliseconds, of the video or reel frame to be used as the cover thumbnail image. The default value is 0, which is the first frame of the video or reel. For reels, if you specify both cover\_url and thumb\_offset, we use cover\_url and ignore thumb\_offset.

upload\_type

\<UPLOAD\_TYPE\>

An optional parameter for users want to upload video through the rupload protocol, values can be set to lowercase string value: resumable.

user\_tags

\<ARRAY\_OF\_USERS\_FOR\_TAGGING\>\>

Required for user tagging in images, videos, and stories. Videos in carousels are not supported. An array of public usernames and x/y coordinates for any public Instagram users who you want to tag in the image. Each object in the array should have the following information:

username — Required. Username.  
x — Required for images, optional for stories. Applies only to images and stories. A float that indicates percentage distance from left edge of the published media image. Value must be within 0.0–1.0 range.  
y — Required for images, optional for stories. Applies only to images and stories. A float that indicates percentage distance from top edge of the published media image. Value must be within 0.0–1.0 range.  
video\_url

\<VIDEO\_URL\>

Required for videos and reels. Applies only to videos and reels. Path to the video. We cURL the video using the passed-in URL, so it must be on a public server.

Response  
A JSON-formatted object containing an IG Container ID which you can use to publish the container.

Video uploads are asynchronous, so receiving a container ID does not guarantee that the upload was successful. To verify that a video has been uploaded, request the status\_code field on the IG Container. If its value is FINISHED, the video was uploaded successfully.

{  
  "id":"\<IG\_CONTAINER\_ID\>"  
}  
Sample Request  
POST graph.facebook.com/17841400008460056/media  
  ?image\_url=curls//www.example.com/images/bronzed-fonzes.jpg  
  \&caption=\#BronzedFonzes\!  
  \&collaborators= \[‘username1’,’username2’\]  
  \&user\_tags=\[  
    {  
      username:'kevinhart4real',  
      x: 0.5,  
      y: 0.8  
    },  
    {  
      username:'therock',  
      x: 0.3,  
      y: 0.2  
    }  
  \]  
Sample Response  
{  
  "id": "17889455560051444"  
}  
Reading  
GET /\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media

Get all IG Media on an IG User.

Limitations  
Returns a maximum of 10K of the most recently created media.  
Story IG Media not supported, use the GET /\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/stories endpoint instead.  
Requirements  
Type	Description  
Access Tokens

User

Permissions

instagram\_basic  
pages\_read\_engagement or pages\_show\_list

If the app user was granted a role on the Page via the Business Manager, you will also need one of:

ads\_management  
business\_management

Time-based Pagination  
This endpoint supports time-based pagination. Include since and until query-string parameters with Unix timestamp or strtotime data values to define a time range.

Sample Request  
GET graph.facebook.com/v24.0/17841405822304914/media  
Sample Response  
{  
  "data": \[  
    {  
      "id": "17895695668004550"  
    },  
    {  
      "id": "17899305451014820"  
    },  
    {  
      "id": "17896450804038745"  
    },  
    {  
      "id": "17881042411086627"  
    },  
    {  
      "id": "17869102915168123"  
    }  
  \]  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
IG Media  
Represents an Instagram album, photo, or video (uploaded video, live video, reel, or story).

If you are migrating from Marketing API Instagram Ads endpoints to Instagram Platform endpoints, be aware that some field names are different.

Introducing the following field:

legacy\_instagram\_media\_id  
The following Marketing API Instagram Ads endpoint fields are not supported:

filter\_name  
location  
location\_name  
latitude  
longitude  
Creating  
This operation is not supported.

Reading  
GET /\<IG\_MEDIA\_ID\>

Gets fields and edges on Instagram media.

Requirements  
Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_basic  
pages\_read\_engagement  
If the app user was granted a role via the Business Manager on the Page connected to your app user's Instagram professional account, your app will also need one of:

ads\_management  
ads\_read  
Limitations  
Fields that return aggregated values don't include ads-driven data. For example, comments\_count returns the number of comments on a photo, but not comments on ads that contain that photo.  
Captions don't include the @ symbol unless the app user is also able to perform admin-equivalent tasks on the app.  
Some fields, such as permalink, cannot be used on photos within albums (children).  
Live video Instagram Media can only be read while they are being broadcast.  
This API returns only data for media owned by Instagram professional accounts. It can not be used to get data for media owned by personal Instagram accounts.  
Request Syntax  
GET https://\<HOST\_URL\>/\<API\_VERSION\>/\<IG\_MEDIA\_ID\> \\  
  ?fields=\<LIST\_OF\_FIELDS\> \\  
  \&access\_token=\<ACCESS\_TOKEN\>  
Path Parameters  
Placeholder	Value  
\<API\_VERSION\>

The latest version is: v24.0	  
The API version your app is using. If not specified in your API calls this will be the latest version at the time you created your Meta app or, if that version is no longer available, the oldest version available.Learn more about versioning.

\<HOST\_URL\>

The host URL your app is using to query the endpoint.

\<IG\_MEDIA\_ID\>

Required. ID for the media to be published.

Query String Parameters  
Key	Placeholder	Value  
access\_token

\<ACCESS\_TOKEN\>

Required. The app user's Facebook or Instagram User access token.

fields

\<LIST\_OF\_FIELDS\>

Comma-separated list of fields you want returned.

Fields  
Public fields can be read via field expansion.

Field	Description  
alt\_text  
Public

Descriptive text for images, for accessibility.

boost\_ads\_list

Offers an overview of all Instagram ad information associated with the organic media for ads with ACTIVE status. It includes relative ad ID and ad delivery status. Available for Instagram API with Facebook Login only.

boost\_eligibility\_info

The field provides information about boosting eligibility of a Instagram instagram media as an ad and additional details if not eligible. Available for Instagram API with Facebook Login only.

caption  
Public

Caption. Excludes album children. The @ symbol is excluded, unless the app user can perform admin-equivalent tasks on the Facebook Page connected to the Instagram account used to create the caption. Available for Instagram API with Facebook Login only.

comments\_count  
Public

Count of comments on the media. Excludes comments on album child media and the media's caption. Includes replies on comments.

copyright\_check\_information.status

Returns status and matches\_found objects

status objects	Description  
status

completed – the detection process has finished  
error – an error occurred during the detection process  
in\_progress – the detection process is ongoing  
not\_started – the detection process has not started  
matches\_found

Set to one of the following:

false if the video does not violate copyright,  
true if the video does violate copyright  
If a video is violating copyright, the copyright\_matches is returned with an array of objects about the copyrighted material, when the violation is occurring in the video, and the actions take to mitigate the violation.

copyright\_matches objects	Description  
author

the author of the copyrighted video

content\_title

the name of the copyrighted video

matched\_segments

An array of objects with the following key-value pairs:

duration\_in\_seconds – the number of seconds the content violates copyright  
segment\_type – either AUDIO or VIDEO  
start\_time\_in\_seconds – set to the start time of the video  
owner\_copyright\_policy

Objects returned include:

name – The name for the copyright owners' policy  
actions – An array of action objects with the mitigations steps taken defined by the copyright owner's policy. May include different mitigations steps for different locations.  
action – The mitigation action taken against the video violating copyright. Different mitigation steps can be taken for different countries. Can be one of the following values:  
BLOCK – The video is blocked from the audiences listed in the geos array  
MUTE \- The video is muted for audiences listed in the geos array  
id  
Public

Media ID.

is\_comment\_enabled

Indicates if comments are enabled or disabled. Excludes album children.

is\_shared\_to\_feed  
Public

For Reels only. When true, indicates that the reel can appear in both the Feed and Reels tabs. When false, indicates that the reel can only appear in the Reels tab.

Neither value determines whether the reel actually appears in the Reels tab because the reel may not meet eligibilty requirements or may not be selected by our algorithm. See reel specifications for eligibility critera.

legacy\_instagram\_media\_id

The ID for Instagram media that was created for Marketing API endpoints for v21.0 and older.

like\_count

Count of likes on the media, including replies on comments. Excludes likes on album child media and likes on promoted posts created from the media.

If queried indirectly through another endpoint or field expansion the like\_count field is omitted if the media owner has hidden like counts.

media\_product\_type  
Public

Surface where the media is published. Can be AD, FEED, STORY or REELS. Available for Instagram API with Facebook Login only.

media\_type  
Public

Media type. Can be CAROUSEL\_ALBUM, IMAGE, or VIDEO.

media\_url  
Public

The URL for the media.

The media\_url field is omitted from responses if the media contains copyrighted material or has been flagged for a copyright violation. Examples of copyrighted material can include audio on reels.

owner  
Public

Instagram user ID who created the media. Only returned if the app user making the query also created the media; otherwise, username field is returned instead.

permalink  
Public

Permanent URL to the media.

shortcode  
Public

Shortcode to the media.

thumbnail\_url  
Public

Media thumbnail URL. Only available on VIDEO media.

timestamp  
Public

ISO 8601-formatted creation date in UTC (default is UTC ±00:00).

username  
Public

Username of user who created the media.

view\_count  
Public

View count for Instagram reels, which includes both paid and organic metrics.

Available for Business Discovery API only.

Edges  
Public edges can be returned through field expansion.

Edge	Description  
children  
Public.

Represents a collection of Instagram Media objects on an album Instagram Media.

collaborators

Represents a list of users who are added as collaborators on an Instagram Media object. Available for Instagram API with Facebook Login only.

comments

Represents a collection of Instagram Comments on an Instagram Media object.

insights

Represents social interaction metrics on an Instagram Media object.

cURL Example  
Example request  
curl \-X GET \\  
  'https://graph.instagram.com/v24.0/17895695668004550?fields=id,media\_type,media\_url,owner,timestamp\&access\_token=IGQVJ...'  
Example response  
{  
  "id": "17918920912340654",  
  "media\_type": "IMAGE",  
  "media\_url": "https://sconten...",  
  "owner": {  
    "id": "17841405309211844"  
  },  
  "timestamp": "2019-09-26T22:36:43+0000"  
}  
Updating  
POST /\<IG\_MEDIA\_ID\>

Enable or disable comments on an Instagram Media.

Requirements  
Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_manage\_comments  
instagram\_basic  
instagram\_manage\_comments  
pages\_read\_engagement  
If the app user was granted a role via the Business Manager on the Page connected to the targeted IG User, you will also need one of:

ads\_management  
ads\_read  
Limitations  
Live video Instagram Media not supported.

Request Syntax  
POST https://\<HOST\_URL\>/\<API\_VERSION\>/\<IG\_MEDIA\_ID\>  
  ?comment\_enabled=\<BOOL\>  
  \&access\_token=\<ACCESS\_TOKEN\>  
Path Parameters  
Placeholder	Value  
\<API\_VERSION\>

The latest version is: v24.0	  
The API version your app is using. If not specified in your API calls this will be the latest version at the time you created your Meta app or, if that version is no longer available, the oldest version available.Learn more about versioning.

\<HOST\_URL\>

The host URL your app is using to query the endpoint.

\<IG\_MEDIA\_ID\>

Required. ID for the media to be published.

Query String Parameters  
Key	Placeholder	Value  
access\_token

\<ACCESS\_TOKEN\>

Required. App user's user access token.

comment\_enabled

\<BOOL\>

Required. Set to true to enable comments or false to disable comments.

cURL Example  
Example request  
curl \-i \-X POST \\  
 "https://graph.instagram.com/v24.0/17918920912340654?comment\_enabled=true\&access\_token=EAAOc..."  
Example response  
{  
  "success": true  
}  
Deleting  
This operation is not supported.  
"

&

"  
Business Discovery  
You can use the Instagram API with Facebook Login to get basic metadata and metrics about other Instagram professional accounts.

Limitations  
Data about age-gated Instagram professional accounts will not be returned.

Endpoints  
The API consists of the following endpoints. Refer to the endpoint's reference documentation for parameter and permission requirements.

GET /\<YOUR\_APP\_USERS\_IG\_USER\_ID\>/business\_discovery  
Examples  
Get Follower & Media Count  
This sample query shows how to get the number of followers and number of published media objects on the Blue Bottle Coffee Instagram professional account. Notice that business discovery queries are performed on the app user's Instagram professional account ID (in this case, 17841405309211844\) with the username of the Instagram professional account that your app user is attempting to get data about (bluebottle in this example).

Sample Request  
Formatted for readability.

curl \-i \-X GET \\  
 "https://graph.facebook.com/v24.0/17841405309211844 \\  
  ?fields=business\_discovery.username(bluebottle){followers\_count,media\_count} \\  
  \&access\_token=\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ACCESS\_TOKEN\>"  
Sample Response  
{  
  "business\_discovery": {  
    "followers\_count": 267793,  
    "media\_count": 1205,  
    "id": "17841401441775531" // Blue Bottle's Instagram user ID  
  },  
  "id": "17841405309211844"  // Your app user's Instagram user ID  
}  
Get Media  
Since you can make nested requests by specifying an edge via the fields parameter, you can request the targeted professional account's media edge to get all of its published media objects.

Sample Request  
Formatted for readability.

curl \-i \-X GET \\  
 "https://graph.facebook.com/v24.0/17841405309211844 \\  
  ?fields=business\_discovery.username(bluebottle){followers\_count,media\_count,media} \\  
  \&access\_token=\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ACCESS\_TOKEN\>"  
Sample Response  
{  
  "business\_discovery": {  
    "followers\_count": 267793,  
    "media\_count": 1205,  
    "media": {  
      "data": \[  
        {  
          "id": "17858843269216389"  
        },  
        {  
          "id": "17894036119131554"  
        },  
        {  
          "id": "17894449363137701"  
        },  
        {  
          "id": "17844278716241265"  
        },  
        ... // results truncated for brevity  
      \],  
    "id": "17841401441775531"  
  },  
  },  
  "id": "17841405309211844"  
}  
Get Basic Metrics on Media  
You can use both nested requests and field expansion to get public fields for a Business or Creator Account's media objects. Note that this does not grant you permission to access media objects directly — performing a GET on any returned IG Media will fail due to insufficient permissions.

For example, here's how to get the number of comments and likes for each of the media objects published by Blue Bottle Coffee:

Please note that view\_count includes both paid and organic metrics

Sample Request  
GET graph.facebook.com  
  /17841405309211844  
    ?fields=business\_discovery.username(bluebottle){media{comments\_count,like\_count,view\_count}}  
Sample Response  
{  
  "business\_discovery": {  
    "media": {  
      "data": \[  
        {  
          "comments\_count": 50,  
          "like\_count": 5837,  
          "view\_count": 7757,  
          "id": "17858843269216389"  
        },  
        {  
          "comments\_count": 11,  
          "like\_count": 2997,  
          "id": "17894036119131554"  
        },  
        {  
          "comments\_count": 28,  
          "like\_count": 3643,  
          "id": "17894449363137701"  
        },  
        {  
          "comments\_count": 43,  
          "like\_count": 4943,  
          "id": "17844278716241265"  
        },  
     \],  
   },  
   "id": "17841401441775531"  
  },  
  "id": "17841405976406927"  
}  
"

&

"

Comment Moderation  
This guide shows you how to get comments, reply to comments, delete comments, hide/unhide comments, and disable/enable comments on Instagram Media owned by your app users using the Instagram Platform.

In this guide we will be using Instagram user and Instagram professional account interchangeably. An Instagram User object represents your app user's Instagram professional account.

Requirements  
This guide assumes you have read the Instagram Platform Overview and implemented the needed components for using this API, such as a Meta login flow and a webhooks server to receive notifications.

You will need the following:

Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook Page access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_manage\_comments  
instagram\_basic  
instagram\_manage\_comments  
pages\_read\_engagement  
If the app user was granted a role on the Page connected to your app user's Instagram professional account via the Business Manager, your app will also need:

ads\_management  
ads\_read  
Webhooks

comments  
live\_comments  
comments  
live\_comments  
Access Level  
Advanced Access if your app serves Instagram professional accounts you don't own or manage  
Standard Access if your app serves Instagram professional accounts you own or manage and have added to your app in the App Dashboard  
Endpoints  
GET /\<IG\_MEDIA\_ID\>/comments — Get comments on an IG Media  
GET /\<IG\_COMMENT\_ID\>/replies — Get replies on an IG Comment  
POST /\<IG\_COMMENT\_ID\>/replies — Reply to an IG Comment  
POST /\<IG\_COMMENT\_ID\> — Hide/unhide an IG Comment  
POST /\<IG\_MEDIA\_ID\> — Disable/enable comments on an IG Media  
DELETE /\<IG\_COMMENT\_ID\> — Delete an IG Comment  
Get comments  
There are two ways to get comments on published Instagram media, an API query or a webhook notification. We strongly recommend using webhooks to prevent rate limiting.

API Request  
To get all the comments on a published Instagram media object, send a GET request to the /\<IG\_MEDIA\_ID\>/comments endpoint.

curl \-X GET "https://\<HOST\_URL\>/v24.0/\<IG\_MEDIA\_ID\>/comments"  
On success your app receives a JSON response with an array of objects containing the comment ID, the comment text, and the time the comment was published.

{  
  "data": \[  
    {  
      "timestamp": "2017-08-31T19:16:02+0000",  
      "text": "This is awesome\!",  
      "id": "17870913679156914"  
    },  
    {  
      "timestamp": "2017-08-31T19:16:02+0000",  
      "text": "Amazing\!",  
      "id": "17870913679156914"  
    },  
		... // results truncated for brevity  
  \]  
}  
Webhooks  
When the comments or live\_comments event is triggered your webhooks server receives a notification that includes the ID for your app user's published media, and the ID for the comments on that media, and the Instagram-scoped ID for the person who published the comment.

Note: When hosting an Instagram Live story, make sure your server can handle the increased load of notifications triggered by live\_comments webhooks events and that your system can differentiate between live\_comments and comments notifications.

Facebook Login for Business  
The following payload is returned for apps that have implemented Facebook Login for Business.

\[  
  {  
    "object": "instagram",  
    "entry": \[  
      {  
        "id": "\<YOUR\_APP\_USERS\_INSTAGRAM\_ACCOUNT\_ID\>",      // ID of your app user's Instagram professional account  
        "time": \<TIME\_META\_SENT\_THIS\_NOTIFICATION\>          // Time Meta sent the notification  
        "changes": \[  
          {  
            "field": "comments",  
            "value": {  
              "from": {  
                "id": "\<INSTAGRAM\_USER\_SCOPED\_ID\>",         // Instagram-scoped ID of the Instagram user who made the comment  
                "username": "\<INSTAGRAM\_USER\_USERNAME\>"     // Username of the Instagram user who made the comment  
              }',  
              "comment\_id": "\<COMMENT\_ID\>",                 // Comment ID of the comment with the mention  
              "parent\_id": "\<PARENT\_COMMENT\_ID\>",           // Parent comment ID, included if the comment was made on a comment  
              "text": "\<TEXT\_ID\>",                          // Comment text, included if comment included text  
              "media": {                                         
                "id": "\<MEDIA\_ID\>",                             // Media's ID that was commented on  
                "ad\_id": "\<AD\_ID\>",                             // Ad's ID, included if the comment was on an ad post  
                "ad\_title": "\<AD\_TITLE\_ID\>",                    // Ad's title, included if the comment was on an ad post  
                "original\_media\_id": "\<ORIGINAL\_MEDIA\_ID\>",     // Original media's ID, included if the comment was on an ad post  
                "media\_product\_type": "\<MEDIA\_PRODUCT\_ID\>"      // Product ID, included if the comment was on a specific product in an ad  
              }  
            }  
          }  
        \]  
      }  
    \]  
  }  
\]  
Business Login for Instagram  
The following payload is returned for apps that have implemented Business Login for Instagram.

\[  
  {  
    "object": "instagram",  
    "entry": \[  
      {  
        "id": "\<YOUR\_APP\_USERS\_INSTAGRAM\_ACCOUNT\_ID\>",  
        "time": \<TIME\_META\_SENT\_THIS\_NOTIFICATION\>

    // Comment or live comment payload  
        "field": "comments",  
        "value": {  
          "id": "\<COMMENT\_ID\>",  
          "from": {  
            "id": "\<INSTAGRAM\_SCOPED\_USER\_ID\>",  
            "username": "\<USERNAME\>"  
          },  
          "text": "\<COMMENT\_TEXT\>",  
          "media": {  
            "id": "\<MEDIA\_ID\>",  
            "media\_product\_type": "\<MEDIA\_PRODUCT\_TYPE\>"  
          }  
        }  
      }  
    \]  
  }  
\]  
Your app can parse the API or webhook notification for comments that match your app user's criteria then use the comment's ID to reply to that comment.

Reply to a comment  
To reply to a comment, send a POST request to the /\<IG\_COMMENT\_ID\>/replies endpoint, where \<IG\_COMMENT\_ID\> is the ID for the comment which you want to reply, with the message parameter set to your message text.

Sample Request  
curl \-X POST "https://\<HOST\_URL\>/v24.0/\<IG\_COMMENT\_ID\>/replies"  
   \-H "Content-Type: application/json"   
   \-d '{  
         "message":"Thanks for sharing\!"  
       }'  
On success, your app receives a JSON response with the comment ID for your comment.

{  
  "id": "17873440459141029"  
}  
If your app user has a lot of comments to reply to, you could batch the replies into a single request.

Next steps  
Learn how to send a message to the person who commented on your app user's media post using Private Replies.

"

&

"  
Insights  
This guide shows you how to get insights for your app users' Instagram media and professional accounts using the Instagram Platform.

In this guide we will be using Instagram user and Instagram professional account interchangeably. An Instagram User object represents your app user's Instagram professional account.

Instagram Insights are now available for Instagram API with Instagram Login. Learn more.

Before you start  
You will need the following:

Requirements  
This guide assumes you have read the Instagram Platform Overview and implemented the needed components for using this API, such as a Meta login flow and a webhooks server to receive notifications.

Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_manage\_insights  
instagram\_basic  
instagram\_manage\_insights  
pages\_read\_engagement  
If the app user was granted a role on the Page connected to your app user's Instagram professional account via the Business Manager, your app will also need:

ads\_management  
ads\_read  
Access Level  
Advanced Access if your app serves Instagram professional accounts you don't own or manage  
Standard Access if your app serves Instagram professional accounts you own or manage and have added to your app in the App Dashboard  
Endpoints  
GET /\<INSTAGRAM\_MEDIA\_ID\>/insights — Gets metrics on a media object  
GET /\<INSTAGRAM\_ACCOUNT\_ID\>/insights — Gets metrics on an Instagram Business Account or Instagram Creator account.  
Refer to each endpoint's reference documentation for additional metrics, parameters, and permission requirements.

UTC  
Timestamps in API responses use UTC with zero offset and are formatted using ISO-8601. For example: 2019-04-05T07:56:32+0000

Webhook event subscriptions  
story\_insights  – Only available for Instagram API with Facebook Login.  
Limitations  
Media insights  
Fields that return aggregated values don't include ads-driven data. For example, comments\_count returns the number of comments on a photo, but not comments on ads that contain that photo.  
Captions don't include the @ symbol unless the app user is also able to perform admin-equivalent tasks on the app.  
Some fields, such as permalink, cannot be used on photos within albums (children).  
Live video Instagram Media can only be read while they are being broadcast.  
This API returns only data for media owned by Instagram professional accounts. It can not be used to get data for media owned by personal Instagram accounts.  
Account insights  
Some metrics are not available on Instagram accounts with fewer than 100 followers.  
User Metrics data is stored for up to 90 days.  
You can only get insights for a single user at a time.  
You cannot get insights for Facebook Pages.  
If insights data you are requesting does not exist or is currently unavailable the API will return an empty data set instead of 0 for individual metrics.  
Examples  
Instagram account request  
The following Instagram API with Facebook Login example is getting the number of impressions, profile\_views, and reach for your app user's Instagram professional account over one 24 hour period.

To get metrics for an Instagram business or creator account, query the GET /\<INSTAGRAM\_USER\_ID\>/insights endpoint with the metrics parameter set to a comma-separated list of the metrics, impressions, profile\_views, and reach, and the period set to day.

GET graph.facebook.com/17841405822304914/insights  
    ?metric=impressions,reach,profile\_views  
    \&period=day  
Sample Response  
On success, your app receives an array for each metric that includes, the metric description, ID of the metric, name and title, the time period over which the metric was measured, and values of the metric.

{  
  "data": \[  
    {  
      "name": "impressions",  
      "period": "day",  
      "values": \[  
        {  
          "value": 32,  
          "end\_time": "2018-01-11T08:00:00+0000"  
        },  
        {  
          "value": 32,  
          "end\_time": "2018-01-12T08:00:00+0000"  
        }  
      \],  
      "title": "Impressions",  
      "description": "Total number of times the Business Account's media objects have been viewed",  
      "id": "instagram\_business\_account\_id/insights/impressions/day"  
    },  
    {  
      "name": "reach",  
      "period": "day",  
      "values": \[  
        {  
          "value": 12,  
          "end\_time": "2018-01-11T08:00:00+0000"  
        },  
        {  
          "value": 12,  
          "end\_time": "2018-01-12T08:00:00+0000"  
        }  
      \],  
      "title": "Reach",  
      "description": "Total number of times the Business Account's media objects have been uniquely viewed",  
      "id": "instagram\_business\_account\_id/insights/reach/day"  
    },  
    {  
      "name": "profile\_views",  
      "period": "day",  
      "values": \[  
        {  
          "value": 15,  
          "end\_time": "2018-01-11T08:00:00+0000"  
        },  
        {  
          "value": 15,  
          "end\_time": "2018-01-12T08:00:00+0000"  
        }  
      \],  
      "title": "Profile Views",  
      "description": "Total number of users who have viewed the Business Account's profile within the specified period",  
      "id": "instagram\_business\_account\_id/insights/profile\_views/day"  
    }  
  \]  
}  
Instagram media request  
The following Instagram API with Instagram Login example is getting the number of engagement, impressions, and reach for your app user's Instagram media over one 24 hour period.

To get metrics for an Instagram business or creator account's media, query the GET /\<INSTAGRAM\_MEDIA\_ID\>/insights endpoint with the metrics parameter set to a comma-separated list of the metrics, engagement, impressions, and reach, and the period set to day.

GET graph.instagram.com/17841491440582230/insights  
    ?metric=engagement,impressions,reach  
Sample Response  
On success, your app receives an array for each metric that includes, the metric description, ID of the metric, name and title, the time period over which the metric was measured, and values of the metric.

{  
  "data": \[  
    {  
      "name": "engagement",  
      "period": "lifetime",  
      "values": \[  
        {  
          "value": 8  
        }  
      \],  
      "title": "Engagement",  
      "description": "Total number of likes and comments on the media object",  
      "id": "media\_id/insights/engagement/lifetime"  
    },  
    {  
      "name": "impressions",  
      "period": "lifetime",  
      "values": \[  
        {  
          "value": 13  
        }  
      \],  
      "title": "Impressions",  
      "description": "Total number of times the media object has been seen",  
      "id": "media\_id/insights/impressions/lifetime"  
    },  
    {  
      "name": "reach",  
      "period": "lifetime",  
      "values": \[  
        {  
          "value": 13  
        }  
      \],  
      "title": "Reach",  
      "description": "Total number of unique accounts that have seen the media object",  
      "id": "media\_id/insights/reach/lifetime"  
    }  
  \]  
}  
"  
&  
"  
Hashtag Search  
Find public IG Media that has been tagged with specific hashtags.

Limitations  
You can query a maximum of 30 unique hashtags on behalf of an Instagram Business or Creator Account within a rolling, 7 day period. Once you query a hashtag, it will count against this limit for 7 days. Subsequent queries on the same hashtag within this time frame will not count against your limit, and will not reset its initial query 7 day timer.  
You cannot comment on hashtagged media objects discovered through the API.  
Hashtags on Stories are not supported.  
Emojis in hashtag queries are not supported.  
The API will return a generic error for any requests that include hashtags that we have deemed sensitive or offensive.  
Requirements  
In order to use this API, you must undergo App Review and request approval for:

the Instagram Public Content Access feature  
the instagram\_basic permission  
Endpoints  
The Hashtag Search API consists of the following nodes and edges:

GET /ig\_hashtag\_search — to get a specific hashtag's node ID  
GET /{ig-hashtag-id} — to get data about a hashtag  
GET /{ig-hashtag-id}/top\_media — to get the most popular photos and videos that have a specific hashtag  
GET /{ig-hashtag-id}/recent\_media — to get the most recently published photos and videos that have a specific hashtag  
GET /{ig-user-id}/recently\_searched\_hashtags — to determine the unique hashtags an Instagram Business or Creator Account has searched for in the current week  
Refer to each endpoint's reference documentation for supported fields, parameters, and usage requirements.

Common Uses  
Getting Media Tagged With A Hashtag  
To get all of the photos and videos that have a specific hashtag, first use the /ig\_hashtag\_search endpoint and include the hashtag and ID of the Instagram Business or Creator Account making the query. For example, if the query is being made on behalf of the Instagram Business Account with the ID 17841405309211844, you could get the ID for the "\#coke" hashtag by performing the following query:

GET graph.facebook.com/ig\_hashtag\_search  
  ?user\_id=17841405309211844  
  \&q=coke  
This will return the ID for the “\#Coke” hashtag node:

{  
  "id" : "17873440459141021"  
}  
Now that you have the hashtag ID (17873440459141021), you can query its /top\_media or /recent\_media edge and include the Business Account ID to get a collection of media objects that have the “\#coke” hashtag. For example:

GET graph.facebook.com/17873440459141021/recent\_media  
  ?user\_id=17841405309211844  
This would return a response that looks like this:

{  
  "data": \[  
    {  
      "id": "17880997618081620"  
    },  
    {  
      "id": "17871527143187462"  
    },  
    {         
      "id": "17896450804038745"       
    }  
  \]  
}  
"  
&  
"  
Mentions  
Identify captions, comments, and IG Media in which an Instagram Business or Creator's alias has been tagged or @mentioned.

Limitations  
Mentions on Stories are not supported.  
Commenting on photos in which you were tagged is not supported.  
Webhooks will not be sent if the Media upon which the comment or @mention appears was created by an account that is set to private.  
Endpoints  
The API consists of the following endpoints:

GET /{ig-user-id}/tags — to get the media objects in which a Business or Creator Account has been tagged  
GET /{ig-user-id}?fields=mentioned\_comment — to get data about a comment that an Business or Creator Account has been @mentioned in  
GET /{ig-user-id}?fields=mentioned\_media — to get data about a media object on which a Business or Creator Account has been @mentioned in a caption  
POST /{ig-user-id}/mentions — to reply to a comment or media object caption that a Business or Creator Account has been @mentioned in by another Instagram user  
Refer to each endpoint reference document for usage instructions.

Webhooks  
Subscribe to the mentions field to recieve Instagram Webhooks notifications whenever an Instagram user mentions an Instagram Business or Creator Account. Note that we do not store Webhooks notification data, so if you set up a Webhook that listens for mentions, you should store any received data if you plan on using it later.

Examples  
Listening for and Replying to Comment @Mentions  
You can listen for comment @mentions and reply to any that meet your criteria:

Set up an Instagram webhook that's subscribed to the mentions field.  
Set up a script that can parse the Webhooks notifications and identify comment IDs.  
Use the IDs to query the GET /{ig-user-id}/mentioned\_comment endpoint to get more information about each comment.  
Analyze the returned results to identify any comments that meet your criteria.  
Use the POST /{ig-user-id}/mentions endpoint to reply to those comments.  
The reply will appear as a sub-thread comment on the comment in which the Business or Creator Account was mentioned.

Listening for and Replying to Caption @Mentions  
You can listen for caption @mentions and reply to any that meet your criteria:

Set up an Instagram webhook that's subscribed to the mentions field.  
Set up a script that can parse the Webhooks notifications and identify media IDs.  
Use the IDs to query the GET /{ig-user-id}/mentioned\_media endpoint to get more information about each media object.  
Analyze the returned results to identify media objects with captions that meet your criteria.  
Use the POST /{ig-user-id}/mentions endpoint to comment on those media objects.  
"  
&  
"  
Content Publishing  
This guide shows you how to publish single images, videos, reels (single media posts), or posts containing multiple images and videos (carousel posts) on Instagram professional accounts using the Instagram Platform.

On March 24, 2025, we introduced the new alt\_text field for image posts on the /\<INSTAGRAM\_PROFESSIONAL\_ACCOUNT\_ID\>/media endpoint. Reels and stories are not supported.

Requirements  
This guide assumes you have read the Instagram Platform Overview and implemented the needed components for using this API, such as a Meta login flow and a webhooks server to receive notifications.

Media on a public server  
We cURL media used in publishing attempts, so the media must be hosted on a publicly accessible server at the time of the attempt.

Page Publishing Authorization  
An Instagram professional account connected to a Page that requires Page Publishing Authorization (PPA) cannot be published to until PPA has been completed.

It's possible that an app user may be able to perform Tasks on a Page that initially does not require PPA but later requires it. In this scenario, the app user would not be able to publish content to their Instagram professional account until completing PPA. Since there's no way for you to determine if an app user's Page requires PPA, we recommend that you advise app users to preemptively complete PPA.

You will need the following:

Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Levels

Advanced Access  
Standard Access  
Advanced Access  
Standard Access  
Access Tokens

Instagram User access token  
Facebook Page access token  
Host URL

graph.instagram.com

graph.facebook.com rupload.facebook.com (For resumable video uploads)

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_content\_publish  
instagram\_basic  
instagram\_content\_publish  
pages\_read\_engagement  
If the app user was granted a role on the Page connected to your app user's Instagram professional account via the Business Manager, your app will also need:

ads\_management  
ads\_read  
Webhooks

Endpoints  
/\<IG\_ID\>/media — Create media container and upload the media  
upload\_type=resumable — Create a resumbable upload session to upload large videos from an area with frequent network interruptions or other transmission failures. Only for apps that have implemented Facebook Login for Business.  
/\<IG\_ID\>/media\_publish — publish uploaded media using their media containers.  
/\<IG\_CONTAINER\_ID\>?fields=status\_code — check media container publishing eligibility and status.  
/\<IG\_ID\>/content\_publishing\_limit — check app user's current publishing rate limit usage.

POST https://rupload.facebook.com/ig-api-upload/\<IG\_MEDIA\_CONTAINER\_ID\> — Upload the video to Meta servers

GET /\<IG\_MEDIA\_CONTAINER\_ID\>?fields=status\_code — Check publishing eligibility and status of the video

HTML URL encoding troubleshooting  
Some of the parameters are supported in list/dict format.  
Some characters need to be encoded into a format that can be transmitted over the Internet. For example: user\_tags=\[{username:’ig\_user\_name’}\] is encoded to user\_tags=%5B%7Busername:ig\_user\_name%7D%5D where \[ is encoded to %5B and { is encoded to %7B. For more conversions, please refer to the HTML URL Encoding standard.  
Limitations  
JPEG is the only image format supported. Extended JPEG formats such as MPO and JPS are not supported.  
Shopping tags are not supported.  
Branded content tags are not supported.  
Filters are not supported.  
For additional limitations, see each endpoint's reference.

Rate Limit  
Instagram accounts are limited to 100 API-published posts within a 24-hour moving period. Carousels count as a single post. This limit is enforced on the POST /\<IG\_ID\>/media\_publish endpoint when attempting to publish a media container. We recommend that your app also enforce the publishing rate limit, especially if your app allows app users to schedule posts to be published in the future.

To check an Instagram professional account's current rate limit usage, query the GET /\<IG\_ID\>/content\_publishing\_limit endpoint.

Create a container  
In order to publish a media object, it must have a container. To create the media container and upload a media file, send a POST request to the /\<IG\_ID\>/media endpoint with the following parameters:

access\_token – Set to your app user's access token  
image\_url or video\_url – Set to the path of the image or video. We will cURL your image using the passed in URL so it must be on a public server.  
media\_type — If the container will be for a video, set to VIDEO, REELS, or STORIES.  
is\_carousel\_item – If the media will be part of a carousel, set to true  
upload\_type – Set to resumable, if creating a resumable upload session for a large video file  
Visit the Instagram User Media Endpoint Reference for additional optional parameters.

Example Request  
Formatted for readability.

curl \-X POST "https://\<HOST\_URL\>/\<LATEST\_API\_VERSION\>/\<IG\_ID\>/media"  
     \-H "Content-Type: application/json"   
     \-H "Authorization: Bearer \<ACCESS\_TOKEN\>"   
     \-d '{  
           "image\_url":"https://www.example.com/images/bronz-fonz.jpg"  
         }'  
On success, your app receives a JSON response with the Instagram Container ID.

{  
  "id": "\<IG\_CONTAINER\_ID\>"    
}  
Create a carousel container  
To publish up to 10 images, videos, or a combination of the two, in a single post, a carousel post, you must create a carousel container. This carousel containter will contain a list of all media containers.

To create the carousel container, send a POST request to the /\<IG\_ID\>/media endpoint with the following parameters:

media\_type — Set to CAROUSEL. Indicates that the container is for a carousel.  
children — A comma separated list of up to 10 container IDs of each image and video that should appear in the published carousel.

Limitations  
Carousels are limited to 10 images, videos, or a mix of the two.  
Carousel images are all cropped based on the first image in the carousel, with the default being a 1:1 aspect ratio.  
Accounts are limited to 50 published posts within a 24-hour period. Publishing a carousel counts as a single post.  
Example Request  
Formatted for readability.

curl \-X POST "https://graph.instagram.com/v24.0/90010177253934/media"  
     \-H "Content-Type: application/json"   
     \-d '{    
           "caption":"Fruit%20candies"  
           "media\_type":"CAROUSEL"  
           "children":"\<IG\_CONTAINER\_ID\_1\>,\<IG\_CONTAINER\_ID\_2\>,\<IG\_CONTAINER\_ID\_3\>"  
         }'  
On success, your app receives a JSON response with the Instagram Carousel Container ID.

{  
  "id": "\<IG\_CAROUSEL\_CONTAINER\_ID\>"   
}  
Resumable Upload Session  
If you created a container for a resumable video upload in Step 1, your need to upload the video before it can be published.

Most API calls use the graph.facebook.com host however, calls to upload videos for Reels use rupload.facebook.com.

The following file sources are supported for uploaded video files:

A file located on your computer  
A file hosted on a public facing server, such as a CDN  
To start the upload session, send a POST request to the /\<IG\_MEDIA\_CONTAINER\_ID endpoint on the rupload.facebook.com host with the following parameters:

access\_token  
Sample request upload a local video file  
With the ig-container-id returned from a resumable upload session call, upload the video.

Be sure the host is rupload.facebook.com.  
All media\_type shares the same flow to upload the video.  
ig-container-id is the ID returned from resumable upload session calls.  
access-token is the same one used in previous steps.  
offset is set to the first byte being upload, generally 0\.  
file\_size is set to the size of your file in bytes.  
Your\_file\_local\_path is set to the file path of your local file, for example, if uploading a file from, the Downloads folder on macOS, the path is @Downloads/example.mov.  
curl \-X POST "https://rupload.facebook.com/ig-api-upload/\<API\_VERSION\>/\<IG\_MEDIA\_CONTAINER\_ID\>\`" \\  
     \-H "Authorization: OAuth \<ACCESS\_TOKEN\>" \\  
     \-H "offset: 0" \\  
     \-H "file\_size: Your\_file\_size\_in\_bytes" \\  
     \--data-binary "@my\_video\_file.mp4"  
Sample request upload a public hosted video  
curl \-X POST "https://rupload.facebook.com/ig-api-upload/\<API\_VERSION\>/\<IG\_MEDIA\_CONTAINER\_ID\>\`" \\  
     \-H "Authorization: OAuth \<ACCESS\_TOKEN\>" \\  
     \-H "file\_url: https://example\_hosted\_video.com"  
Sample Response  
// Success Response Message  
{  
  "success":true,  
  "message":"Upload successful."  
}

// Failure Response Message  
{  
  "debug\_info":{  
    "retriable":false,  
    "type":"ProcessingFailedError",  
    "message":"{\\"success\\":false,\\"error\\":{\\"message\\":\\"unauthorized user request\\"}}"  
  }  
}  
Publish the container  
To publish the media,

Send a POST request to the /\<IG\_ID\>/media\_publish endpoint with the following parameters:

creation\_id set to the container ID, either for a single media container or a carousel container  
Example Request  
Formatted for readability.

      
curl \-X POST "https://\<HOST\_URL\>/\<LATEST\_API\_VERSION\>/\<IG\_ID\>/media\_publish"  
     \-H "Content-Type: application/json"   
     \-H "Authorization: Bearer \<ACCESS\_TOKEN\>"       
     \-d '{  
           "creation\_id":"\<IG\_CONTAINER\_ID\>"   
         }'  
On success, your app receives a JSON response with the Instagram Media ID.

{  
  "id": "\<IG\_MEDIA\_ID\>"  
}  
Reels posts  
Reels are short-form videos that appears in the Reels tab of the Instagram app. To publish a reel, create a container for the video and include the media\_type=REELS parameter along with the path to the video using the video\_url parameter.

If you publish a reel and then request its media\_type field, the value returned is VIDEO. To determine if a published video has been designated as a reel, request its media\_product\_type field instead.

You can use the code sample on GitHub (insta\_reels\_publishing\_api\_sample) to learn how to publish Reels to Instagram.

Story posts  
To publish a reel, create a container for the media object and include the media\_type parameter set to STORIES.

If you publish a story and then request its media\_type field, the value will be returned as IMAGE/VIDEO. To determine if a published image/video is a story, request its media\_product\_type field instead.

Troubleshooting  
If you are able to create a container for a video but the POST /\<IG\_ID\>/media\_publish endpoint does not return the published media ID, you can get the container's publishing status by querying the GET /\<IG\_CONTAINER\_ID\>?fields=status\_code endpoint. This endpoint will return one of the following:

EXPIRED — The container was not published within 24 hours and has expired.  
ERROR — The container failed to complete the publishing process.  
FINISHED — The container and its media object are ready to be published.  
IN\_PROGRESS — The container is still in the publishing process.  
PUBLISHED — The container's media object has been published.  
We recommend querying a container's status once per minute, for no more than 5 minutes.

Errors  
See the Error Codes reference.

"

&

"  
Overview  
The Graph API is the primary way to get data into and out of the Facebook platform. It's an HTTP-based API that apps can use to programmatically query data, post new stories, manage ads, upload photos, and perform a wide variety of other tasks.

The Graph API is named after the idea of a "social graph" — a representation of the information on Facebook. It's composed of nodes, edges, and fields. Typically you use nodes to get data about a specific object, use edges to get collections of objects on a single object, and use fields to get data about a single object or each object in a collection. Throughout our documentation, we may refer to both a node and edge as an "endpoint". For example, "send a GET request to the User endpoint".

HTTP  
All data transfers conform to HTTP/1.1, and all endpoints require HTTPS. Because the Graph API is HTTP-based, it works with any language that has an HTTP library, such as cURL and urllib. This means you can use the Graph API directly in your browser. For example, requesting this URL in your browser...

https://graph.facebook.com/facebook/picture?redirect=false

... is equivalent to performing this cURL request:

curl \-i \-X GET "https://graph.facebook.com/facebook/picture?redirect=false"  
We have also enabled the includeSubdomains HSTS directive on facebook.com, but this should not adversely affect your Graph API calls.

Host URL  
Almost all requests are passed to the graph.facebook.com host URL. The single exception is video uploads, which use graph-video.facebook.com.

Access Tokens  
Access tokens allow your app to access the Graph API. Almost all Graph API endpoints require an access token of some kind, so each time you access an endpoint, your request may require one. They typically perform two functions:

They allow your app to access a User's information without requiring the User's password. For example, your app needs a User's email to perform a function. If the User agrees to allow your app to retrieve their email address from Facebook, the User will not need to enter their Facebook password for your app to get their email address.  
They allow us to identify your app, the User who is using your app, and the type of data the User has permitted your app to access.  
Visit our access token documentation to learn more.

Nodes  
A node is an individual object with a unique ID. For example, there are many User node objects, each with a unique ID representing a person on Facebook. Pages, Groups, Posts, Photos, and Comments are just some of the nodes of the Facebook Social Graph.

The following cURL example represents a call to the User node.

curl \-i \-X GET \\  
  "https://graph.facebook.com/USER-ID?access\_token=ACCESS-TOKEN"  
This request would return the following data by default, formatted using JSON:

{  
  "name": "Your Name",  
  "id": "YOUR-USER-ID"  
}  
Node Metadata  
You can get a list of all fields, including the field name, description, and data type, of a node object, such as a User, Page, or Photo. Send a GET request to an object ID and include the metadata=1 parameter:

curl \-i \-X GET \\  
  "https://graph.facebook.com/USER-ID?  
    metadata=1\&access\_token=ACCESS-TOKEN"  
The resulting JSON response will include the metadata property that lists all the supported fields for the given node:

{  
  "name": "Jane Smith",  
  "metadata": {  
    "fields": \[  
      {  
        "name": "id",  
        "description": "The app user's App-Scoped User ID. This ID is unique to the app and cannot be used by other apps.",  
        "type": "numeric string"  
      },  
      {  
        "name": "age\_range",  
        "description": "The age segment for this person expressed as a minimum and maximum age. For example, more than 18, less than 21.",  
        "type": "agerange"  
      },  
      {  
        "name": "birthday",  
        "description": "The person's birthday.  This is a fixed format string, like \`MM/DD/YYYY\`.  However, people can control who can see the year they were born separately from the month and day so this string can be only the year (YYYY) or the month \+ day (MM/DD)",  
        "type": "string"  
      },  
...  
/me  
The /me node is a special endpoint that translates to the object ID of the person or Page whose access token is currently being used to make the API calls. If you had a User access token, you could retrieve a User's name and ID by using:

curl \-i \-X GET \\  
  "https://graph.facebook.com/me?access\_token=ACCESS-TOKEN"  
Edges  
An edge is a connection between two nodes. For example, a User node can have photos connected to it, and a Photo node can have comments connected to it. The following cURL example will return a list of photos a person has published to Facebook.

curl \-i \-X GET \\  
  "https://graph.facebook.com/USER-ID/photos?access\_token=ACCESS-TOKEN"  
Each ID returned represents a Photo node and when it was uploaded to Facebook.

    {  
  "data": \[  
    {  
      "created\_time": "2017-06-06T18:04:10+0000",  
      "id": "1353272134728652"  
    },  
    {  
      "created\_time": "2017-06-06T18:01:13+0000",  
      "id": "1353269908062208"  
    }  
  \],  
}  
Fields  
Fields are node properties. When you query a node, or an edge, it returns a set of fields by default, as the examples above show. However, you can specify which fields you want returned by using the fields parameter and listing each field. This overrides the defaults and returns only the fields you specify, and the ID of the object, which is always returned.

The following cURL request includes the fields parameter and the User's name, email, and profile picture.

curl \-i \-X GET \\  
  "https://graph.facebook.com/USER-ID?fields=id,name,email,picture\&access\_token=ACCESS-TOKEN"  
Data Returned  
{  
  "id": "USER-ID",  
  "name": "EXAMPLE NAME",  
  "email": "EXAMPLE@EMAIL.COM",  
  "picture": {  
    "data": {  
      "height": 50,  
      "is\_silhouette": false,  
      "url": "URL-FOR-USER-PROFILE-PICTURE",  
      "width": 50  
    }  
  }  
}  
Complex Parameters  
Most parameter types are straightforward primitives such as bool, string and int, but there are also list and object types that can be specified in the request.

The list type is specified in JSON syntax, for example: \["firstitem", "seconditem", "thirditem"\]

The object type is also specified in JSON syntax, for example: {"firstkey": "firstvalue", "secondKey": 123}

Publishing, Updating, and Deleting  
Visit our Facebook Sharing guide to learn how to publish to a User's Facebook or our Pages API documentation to publish to a Page's Facebook feed.

Some nodes allow you to update fields with POST operations. For example, you could update your email field like this:

curl \-i \-X POST \\  
  "https://graph.facebook.com/USER-ID?email=YOURNEW@EMAILADDRESS.COM\&access\_token=ACCESS-TOKEN"  
Read-After-Write  
For create and update endpoints, the Graph API can immediately read a successfully published or updated object and return any fields supported by the corresponding read endpoint.

By default, an ID of the object created or updated will be returned. To include more information in the response, include the fields parameter in your request and list the fields you want returned. For example, to publish the message “Hello” to a Page's feed, you could make the following request:

curl \-i \- X POST "https://graph.facebook.com/PAGE-ID/feed?message=Hello&  
  fields=created\_time,from,id,message\&access\_token=ACCESS-TOKEN"  
The above code example is formatted for readability.  
This would return the specified fields as a JSON-formatted response, like this:

{  
  "created\_time": "2017-04-06T22:04:21+0000",  
  "from": {  
    "name": "My Facebook Page",  
    "id": "PAGE-ID"  
  },  
  "id": "POST\_ID",  
  "message": "Hello",  
}  
Refer to each endpoint's reference documentation to see if it supports read-after-write and what fields are available.

Errors  
If the read fails for any reason (for example, requesting a non-existent field), the Graph API will respond with a standard error response. Visit our Handling Errors guide for more information.

You can usually delete a node, such as a Post or Photo node, by performing a DELETE operation on the object ID:

curl \-i \-X DELETE \\  
  "https://graph.facebook.com/PHOTO-ID?access\_token=ACCESSS-TOKEN"  
Usually you can only delete nodes that you created, but check each node's reference guide to see requirements for delete operations.

Webhooks  
You can be notified of changes to nodes or interactions with nodes by subscribing to webhooks. See Webhooks.

Versions  
The Graph API has multiple versions with quarterly releases. You can specify the version in your calls by adding "v" and the version number to the start of the request path. For example, here's a call to version 4.0:

curl \-i \-X GET \\  
  "https://graph.facebook.com/v4.0/USER-ID/photos  
    ?access\_token=ACCESS-TOKEN"  
If you do not include a version number we will default to the oldest available version, so it's recommended to include the version number in your requests.

You can read more about versions in our Versioning guide and learn about all available versions in the Graph API Changelog.

Facebook APIs, SDKs, and Platforms  
Connect interfaces and develop across platforms using Facebook's various APIs, SDKs, and platforms.

"  
&  
"  
Getting Started  
This document explains how to successfully call the Instagram API with Facebook Login for Business with your app and get an Instagram Business or Creator Account's media objects. It assumes you are familiar with and how to perform REST API calls. If you do not have an app yet, you can use the Graph API Explorer instead and skip steps 1 and 2\.

Before You Start  
You will need access to the following:

An Instagram Business Account or Instagram Creator Account  
A Facebook Page connected to that account  
A Facebook Developer account that can perform Tasks on that Page  
A registered Facebook App with Basic settings configured  
1\. Configure Facebook Login for Business  
Add the Facebook Login product to your app in the App Dashboard.

You can leave all settings on their defaults. If you are implementing Facebook Login for Business manually (which we don't recommend), enter your redirect\_uri in the Valid OAuth redirect URIs field. If you will be using one of our SDKs, you can leave it blank.

2\. Implement Facebook Login for Business  
Follow our Facebook Login for Business documentation for your platform and implement the login into your app. Set up your implementation to request these permissions:

instagram\_basic  
pages\_show\_list  
3\. Get a User Access Token  
Once you've implemented Facebook Login for Business, make sure you are signed into your Facebook Developer account, then access your app and trigger the Facebook Login for Business modal. Remember, your Facebook Developer account must be able to perform Tasks on the Facebook Page connected to the Instagram account you want to query.

Once you have triggered the modal, click OK to grant your app the instagram\_basic and pages\_show\_list permissions.

The API should return a User access token. Capture the token so your app can use it in the next few queries. If you are using the Graph API Explorer, it will be captured automatically and displayed in the Access Token field for reference:

4\. Get the User's Pages  
Query the GET /me/accounts endpoint (this translates to GET /{user-id}/accounts, which perform a GET on the Facebook User node, based on your access token).

curl \-i \-X GET \\  
 "https://graph.facebook.com/v24.0/me/accounts?access\_token={access-token}"  
This should return a collection of Facebook Pages that the current Facebook User can perform the MANAGE, CREATE\_CONTENT, MODERATE, or ADVERTISE tasks on:

{  
  "data": \[  
    {  
      "access\_token": "EAAJjmJ...",  
      "category": "App Page",  
      "category\_list": \[  
        {  
          "id": "2301",  
          "name": "App Page"  
        }  
      \],  
      "name": "Metricsaurus",  
      "id": "134895793791914",  // capture the Page ID  
      "tasks": \[  
        "ANALYZE",  
        "ADVERTISE",  
        "MODERATE",  
        "CREATE\_CONTENT",  
        "MANAGE"  
      \]  
    }  
  \]  
}  
Capture the ID of the Facebook Page that's connected to the Instagram account that you want to query. Keep in mind that your app users may be able to perform tasks on multiple pages, so you eventually will have to introduce logic that can determine the correct Page ID to capture (or devise a UI where your app users can identify the correct Page for you).

5\. Get the Page's Instagram Business Account  
Use the Page ID you captured to query the GET /{page-id}?fields=instagram\_business\_account endpoint:

curl \-i \-X GET \\  
 "https://graph.facebook.com/v24.0/134895793791914?fields=instagram\_business\_account\&access\_token={access-token}"  
This should return the IG User — an Instagram Business or Creator Account — that's connected to the Facebook Page.

{  
  "instagram\_business\_account": {  
    "id": "17841405822304914"  // Connected IG User ID  
  },  
  "id": "134895793791914"  // Facebook Page ID  
}  
Capture the IG User ID.

6\. Get the Instagram Business Account's Media Objects  
Use the IG User ID you captured to query the GET /{ig-user-id}/media endpoint:

curl \-i \-X GET \\  
 "https://graph.facebook.com/v24.0/17841405822304914/media?access\_token={access-token}"  
This should return the IDs of all the IG Media objects on the IG User:

{  
  "data": \[  
    {  
      "id": "17918195224117851"  
    },  
    {  
      "id": "17895695668004550"  
    },  
    {  
      "id": "17899305451014820"  
    },  
    {  
      "id": "17896450804038745"  
    },  
    {  
      "id": "17881042411086627"  
    },  
    {  
      "id": "17869102915168123"  
    }  
  \],  
  "paging": {  
    "cursors": {  
      "before": "QVFIUkdGRXA2eHNNTUs4T1ZAXNGFxQTAtd3U4QjBLd1B2NXRMM1NkcnhqRFdBcEUzSDVJZATFoLWtXMWZAGU2VrRTk2RHVtTVlDckI2NjN0UERFa2JrUk4yMW13",  
      "after": "QVFIUmlwbnFsM3N2cV9lZAFdCa0hDeV9qMVliT0VuMmJyNENxZA180c0t6VjFQVEJaTE9XV085aU92OUFLNFB6Szd2amo5aV9rTlVBcnNlWmEtMzYxcE1HSFR3"  
    }  
  }  
}

If you are able to perform this final query successfully, you should be able to perform queries using any of the Instagram Platform endpoints — just refer to our various guides and references to learn what each endpoint can do and what permissions they require.

Next Steps  
Develop your app further so it can successfully use any other endpoints it needs, and keep track of the permissions each endpoint requires  
If you plan to implement Instagram Messaging from Messenger Platform you will need additional permissions  
Complete the App Review process and request approval for all of the permissions your app will need so your app users can grant them while your app is in Live Mode  
Switch your app to Live Mode and market it to potential users  
Once your app is in Live Mode, any Facebook User who you've made your app available to can access an Instagram Business or Creator Account's data, as long as they have a Facebook User account that can perform Tasks on the Page connected to that Instagram Business or Creator Account.  
"  
&  
"  
Content Publishing  
This guide shows you how to publish single images, videos, reels (single media posts), or posts containing multiple images and videos (carousel posts) on Instagram professional accounts using the Instagram Platform.

On March 24, 2025, we introduced the new alt\_text field for image posts on the /\<INSTAGRAM\_PROFESSIONAL\_ACCOUNT\_ID\>/media endpoint. Reels and stories are not supported.

Requirements  
This guide assumes you have read the Instagram Platform Overview and implemented the needed components for using this API, such as a Meta login flow and a webhooks server to receive notifications.

Media on a public server  
We cURL media used in publishing attempts, so the media must be hosted on a publicly accessible server at the time of the attempt.

Page Publishing Authorization  
An Instagram professional account connected to a Page that requires Page Publishing Authorization (PPA) cannot be published to until PPA has been completed.

It's possible that an app user may be able to perform Tasks on a Page that initially does not require PPA but later requires it. In this scenario, the app user would not be able to publish content to their Instagram professional account until completing PPA. Since there's no way for you to determine if an app user's Page requires PPA, we recommend that you advise app users to preemptively complete PPA.

You will need the following:

Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Levels

Advanced Access  
Standard Access  
Advanced Access  
Standard Access  
Access Tokens

Instagram User access token  
Facebook Page access token  
Host URL

graph.instagram.com

graph.facebook.com rupload.facebook.com (For resumable video uploads)

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_content\_publish  
instagram\_basic  
instagram\_content\_publish  
pages\_read\_engagement  
If the app user was granted a role on the Page connected to your app user's Instagram professional account via the Business Manager, your app will also need:

ads\_management  
ads\_read  
Webhooks

Endpoints  
/\<IG\_ID\>/media — Create media container and upload the media  
upload\_type=resumable — Create a resumbable upload session to upload large videos from an area with frequent network interruptions or other transmission failures. Only for apps that have implemented Facebook Login for Business.  
/\<IG\_ID\>/media\_publish — publish uploaded media using their media containers.  
/\<IG\_CONTAINER\_ID\>?fields=status\_code — check media container publishing eligibility and status.  
/\<IG\_ID\>/content\_publishing\_limit — check app user's current publishing rate limit usage.

POST https://rupload.facebook.com/ig-api-upload/\<IG\_MEDIA\_CONTAINER\_ID\> — Upload the video to Meta servers

GET /\<IG\_MEDIA\_CONTAINER\_ID\>?fields=status\_code — Check publishing eligibility and status of the video

HTML URL encoding troubleshooting  
Some of the parameters are supported in list/dict format.  
Some characters need to be encoded into a format that can be transmitted over the Internet. For example: user\_tags=\[{username:’ig\_user\_name’}\] is encoded to user\_tags=%5B%7Busername:ig\_user\_name%7D%5D where \[ is encoded to %5B and { is encoded to %7B. For more conversions, please refer to the HTML URL Encoding standard.  
Limitations  
JPEG is the only image format supported. Extended JPEG formats such as MPO and JPS are not supported.  
Shopping tags are not supported.  
Branded content tags are not supported.  
Filters are not supported.  
For additional limitations, see each endpoint's reference.

Rate Limit  
Instagram accounts are limited to 100 API-published posts within a 24-hour moving period. Carousels count as a single post. This limit is enforced on the POST /\<IG\_ID\>/media\_publish endpoint when attempting to publish a media container. We recommend that your app also enforce the publishing rate limit, especially if your app allows app users to schedule posts to be published in the future.

To check an Instagram professional account's current rate limit usage, query the GET /\<IG\_ID\>/content\_publishing\_limit endpoint.

Create a container  
In order to publish a media object, it must have a container. To create the media container and upload a media file, send a POST request to the /\<IG\_ID\>/media endpoint with the following parameters:

access\_token – Set to your app user's access token  
image\_url or video\_url – Set to the path of the image or video. We will cURL your image using the passed in URL so it must be on a public server.  
media\_type — If the container will be for a video, set to VIDEO, REELS, or STORIES.  
is\_carousel\_item – If the media will be part of a carousel, set to true  
upload\_type – Set to resumable, if creating a resumable upload session for a large video file  
Visit the Instagram User Media Endpoint Reference for additional optional parameters.

Example Request  
Formatted for readability.

curl \-X POST "https://\<HOST\_URL\>/\<LATEST\_API\_VERSION\>/\<IG\_ID\>/media"  
     \-H "Content-Type: application/json"   
     \-H "Authorization: Bearer \<ACCESS\_TOKEN\>"   
     \-d '{  
           "image\_url":"https://www.example.com/images/bronz-fonz.jpg"  
         }'  
On success, your app receives a JSON response with the Instagram Container ID.

{  
  "id": "\<IG\_CONTAINER\_ID\>"    
}  
Create a carousel container  
To publish up to 10 images, videos, or a combination of the two, in a single post, a carousel post, you must create a carousel container. This carousel containter will contain a list of all media containers.

To create the carousel container, send a POST request to the /\<IG\_ID\>/media endpoint with the following parameters:

media\_type — Set to CAROUSEL. Indicates that the container is for a carousel.  
children — A comma separated list of up to 10 container IDs of each image and video that should appear in the published carousel.

Limitations  
Carousels are limited to 10 images, videos, or a mix of the two.  
Carousel images are all cropped based on the first image in the carousel, with the default being a 1:1 aspect ratio.  
Accounts are limited to 50 published posts within a 24-hour period. Publishing a carousel counts as a single post.  
Example Request  
Formatted for readability.

curl \-X POST "https://graph.instagram.com/v24.0/90010177253934/media"  
     \-H "Content-Type: application/json"   
     \-d '{    
           "caption":"Fruit%20candies"  
           "media\_type":"CAROUSEL"  
           "children":"\<IG\_CONTAINER\_ID\_1\>,\<IG\_CONTAINER\_ID\_2\>,\<IG\_CONTAINER\_ID\_3\>"  
         }'  
On success, your app receives a JSON response with the Instagram Carousel Container ID.

{  
  "id": "\<IG\_CAROUSEL\_CONTAINER\_ID\>"   
}  
Resumable Upload Session  
If you created a container for a resumable video upload in Step 1, your need to upload the video before it can be published.

Most API calls use the graph.facebook.com host however, calls to upload videos for Reels use rupload.facebook.com.

The following file sources are supported for uploaded video files:

A file located on your computer  
A file hosted on a public facing server, such as a CDN  
To start the upload session, send a POST request to the /\<IG\_MEDIA\_CONTAINER\_ID endpoint on the rupload.facebook.com host with the following parameters:

access\_token  
Sample request upload a local video file  
With the ig-container-id returned from a resumable upload session call, upload the video.

Be sure the host is rupload.facebook.com.  
All media\_type shares the same flow to upload the video.  
ig-container-id is the ID returned from resumable upload session calls.  
access-token is the same one used in previous steps.  
offset is set to the first byte being upload, generally 0\.  
file\_size is set to the size of your file in bytes.  
Your\_file\_local\_path is set to the file path of your local file, for example, if uploading a file from, the Downloads folder on macOS, the path is @Downloads/example.mov.  
curl \-X POST "https://rupload.facebook.com/ig-api-upload/\<API\_VERSION\>/\<IG\_MEDIA\_CONTAINER\_ID\>\`" \\  
     \-H "Authorization: OAuth \<ACCESS\_TOKEN\>" \\  
     \-H "offset: 0" \\  
     \-H "file\_size: Your\_file\_size\_in\_bytes" \\  
     \--data-binary "@my\_video\_file.mp4"  
Sample request upload a public hosted video  
curl \-X POST "https://rupload.facebook.com/ig-api-upload/\<API\_VERSION\>/\<IG\_MEDIA\_CONTAINER\_ID\>\`" \\  
     \-H "Authorization: OAuth \<ACCESS\_TOKEN\>" \\  
     \-H "file\_url: https://example\_hosted\_video.com"  
Sample Response  
// Success Response Message  
{  
  "success":true,  
  "message":"Upload successful."  
}

// Failure Response Message  
{  
  "debug\_info":{  
    "retriable":false,  
    "type":"ProcessingFailedError",  
    "message":"{\\"success\\":false,\\"error\\":{\\"message\\":\\"unauthorized user request\\"}}"  
  }  
}  
Publish the container  
To publish the media,

Send a POST request to the /\<IG\_ID\>/media\_publish endpoint with the following parameters:

creation\_id set to the container ID, either for a single media container or a carousel container  
Example Request  
Formatted for readability.

      
curl \-X POST "https://\<HOST\_URL\>/\<LATEST\_API\_VERSION\>/\<IG\_ID\>/media\_publish"  
     \-H "Content-Type: application/json"   
     \-H "Authorization: Bearer \<ACCESS\_TOKEN\>"       
     \-d '{  
           "creation\_id":"\<IG\_CONTAINER\_ID\>"   
         }'  
On success, your app receives a JSON response with the Instagram Media ID.

{  
  "id": "\<IG\_MEDIA\_ID\>"  
}  
Reels posts  
Reels are short-form videos that appears in the Reels tab of the Instagram app. To publish a reel, create a container for the video and include the media\_type=REELS parameter along with the path to the video using the video\_url parameter.

If you publish a reel and then request its media\_type field, the value returned is VIDEO. To determine if a published video has been designated as a reel, request its media\_product\_type field instead.

You can use the code sample on GitHub (insta\_reels\_publishing\_api\_sample) to learn how to publish Reels to Instagram.

Story posts  
To publish a reel, create a container for the media object and include the media\_type parameter set to STORIES.

If you publish a story and then request its media\_type field, the value will be returned as IMAGE/VIDEO. To determine if a published image/video is a story, request its media\_product\_type field instead.

Troubleshooting  
If you are able to create a container for a video but the POST /\<IG\_ID\>/media\_publish endpoint does not return the published media ID, you can get the container's publishing status by querying the GET /\<IG\_CONTAINER\_ID\>?fields=status\_code endpoint. This endpoint will return one of the following:

EXPIRED — The container was not published within 24 hours and has expired.  
ERROR — The container failed to complete the publishing process.  
FINISHED — The container and its media object are ready to be published.  
IN\_PROGRESS — The container is still in the publishing process.  
PUBLISHED — The container's media object has been published.  
We recommend querying a container's status once per minute, for no more than 5 minutes.

Errors  
See the Error Codes reference.

Next Steps  
"  
&

"  
Upload Video to Meta Servers  
This guide shows you how to upload large video files, from local and publicly hosted content, to be published on Instagram. This is available only for apps that have implemented Facebook Login for Business.

The API allows you resume a local file upload operation after a network interruption or other transmission failure, saving time and bandwidth in the event of network failures.

Host URLs  
graph.facebook.com – Create video media containers and publish and manage uploaded media  
rupload.facebook.com – Upload the video to Meta servers  
Endpoints  
POST https://graph.facebook.com/\<IG\_USER\_ID\>/media?upload\_type=resumable — Initialize the upload and create a media container for the video  
POST https://rupload.facebook.com/ig-api-upload/\<IG\_MEDIA\_CONTAINER\_ID\> — Upload the video to Meta servers  
POST https://graph.facebook.com/\<IG\_USER\_ID\>/media\_publish?creation\_id=\<IG\_MEDIA\_CONTAINER\_ID\> — Publish the uploaded video  
GET /\<IG\_MEDIA\_CONTAINER\_ID\>?fields=status\_code — Check publishing eligibility and status of the video  
HTML URL encoding hints  
Some of the parameters are supported in list/dict format.  
Some characters need to be encoded into a format that can be transmitted over the Internet. For example: user\_tags=\[{username:’ig\_user\_name’}\] is encoded to user\_tags=%5B%7Busername:ig\_user\_name%7D%5D where \[ is encoded to %5B and { is encoded to %7B. For more conversions, please refer to the HTML URL Encoding standard.  
Create a container  
To create a resumable upload session for the video, send a POST request to the /\<IG\_USER\_ID\>/media endpoint on the graph.facebook.com host with the following required parameters:

access\_token – Set to your app user's access token  
upload\_type – Set to resumable  
media\_type – Set to REELS, STORIES, or VIDEO (for videos to be used in carousels)  
is\_carousel\_item – Set to true (for videos to be used in carousels)  
Basic example request  
Formatted for readability.

curl "https://graph.facebook.com/\<API\_VERSION\>/\<IG\_USER\_ID\>/media"   
     \-H "Content-Type: application/json"  
     \-H "Authorization: Bearer \<USER\_ACCESS\_TOKEN\>"  
     \-d '{    
            "media\_type": "\<REELS\_STORIES\_VIDEO\>"  
            "upload\_type=resumable"  
        }'  
Optional parameters for Reels  
audio\_name – Set to the name of the audio  
caption – Set to the caption for the reel video  
collaborators – Set to a comma-separated list of up to 3 Instagram usernames of collaborators  
cover\_url – Set to the URL to the cover image for the Reels tab  
location\_id – Set to the ID of a Facebook Page associated with a location  
thumb\_offset – Set to frame in the video to be used as the thumbnail  
user\_tags – Set to an array of username objects for public Instagram users your app user wants to tag in the video  
Sample response  
On success your app receives a JSON object with the ID and the Meta URI for the container. These two values will be used in Step 2\.

{  
   "id": "\<IG\_MEDIA\_CONTAINER\_ID\>",  
   "uri": "https://rupload.facebook.com/ig-api-upload/\<API\_VERSION\>/\<IG\_MEDIA\_CONTAINER\_ID\>\`"  
}  
Upload the Video  
Most Graph API calls use the graph.facebook.com host however, calls to upload videos for Reels use rupload.facebook.com.

The following file sources are supported for uploaded video files:

A file located on your computer  
A file hosted on a public facing server, such as a CDN  
Sample request upload a local video file  
With the ig-container-id returned from a resumable upload session call, upload the video.

Be sure the host is rupload.facebook.com.  
All media\_type shares the same flow to upload the video.  
ig-container-id is the ID returned from resumable upload session calls.  
access-token is the same one used in previous steps.  
offset is set to the first byte being upload, generally 0\.  
file\_size is set to the size of your file in bytes.  
Your\_file\_local\_path is set to the file path of your local file, for example, if uploading a file from, the Downloads folder on macOS, the path is @Downloads/example.mov.  
curl \-X POST "https://rupload.facebook.com/ig-api-upload/\<API\_VERSION\>/\<IG\_MEDIA\_CONTAINER\_ID\>\`" \\  
     \-H "Authorization: OAuth \<ACCESS\_TOKEN\>" \\  
     \-H "offset: 0" \\  
     \-H "file\_size: Your\_file\_size\_in\_bytes" \\  
     \--data-binary "@my\_video\_file.mp4"  
Sample request upload a public hosted video  
curl \-X POST "https://rupload.facebook.com/ig-api-upload/\<API\_VERSION\>/\<IG\_MEDIA\_CONTAINER\_ID\>\`" \\  
     \-H "Authorization: OAuth \<ACCESS\_TOKEN\>" \\  
     \-H "file\_url: https://example\_hosted\_video.com"  
Sample Response  
// Success Response Message  
{  
  "success":true,  
  "message":"Upload successful."  
}

// Failure Response Message  
{  
  "debug\_info":{  
    "retriable":false,  
    "type":"ProcessingFailedError",  
    "message":"{\\"success\\":false,\\"error\\":{\\"message\\":\\"unauthorized user request\\"}}"  
  }  
}  
Step 3: (Carousel Only) Create Carousel Containers  
You can reuse step 1 and 2 to create multiple ig-container-ids with the is\_carousel\_item parameter set to true. Then create a Carousel Container to include all the carousel items, the carousel items can be mixed with Image and Videos.

curl \-X POST "https://graph.facebook.com/\<API\_VERSION\>/\<IG\_USER\_ID\>/media" \\  
    \-d "media\_type=CAROUSEL" \\  
    \-d "caption={caption}"\\  
    \-d "collaborators={collaborator-usernames}" \\  
    \-d "location\_id={location-id}" \\  
    \-d "product\_tags={product-tags}" \\  
    \-d "children=\[\<IG\_MEDIA\_CONTAINER\_ID\_1\>\`,\<IG\_MEDIA\_CONTAINER\_ID\_2\>\`...\]" \\  
    \-H "Authorization: OAuth \<ACCESS\_TOKEN\>"  
Step 4: Publish the Media  
For Reels and Video Stories, the \<IG\_MEDIA\_CONTAINER\_ID\>\`\` created in step 1 is used to publish the Video, and for Carousel Container, the\<IG\_MEDIA\_CONTAINER\_ID\>\`\` created in step 3 is used to publish the Carousel Container.

curl \-X POST "https://graph.facebook.com/\<API\_VERSION\>/\<IG\_USER\_ID\>/media\_publish" \\  
    \-d "creation\_id=\<IG\_MEDIA\_CONTAINER\_ID\>\`" \\  
    \-H "Authorization: OAuth \<ACCESS\_TOKEN\>"  
Step 5: Get Media Status  
graph.facebook.com provides a GET endpoint to read the status of the upload, the video\_status field contains details about the local upload process.

The uploading\_phase tells whether the file has been uploaded successfully, and how many bytes transferred.  
The processing\_phase contains the details about the status of video processing after the video file is uploaded.  
// GET status from graph.facebook.com  
curl \-X GET "https://graph.facebook.com/v19.0/\<IG\_MEDIA\_CONTAINER\_ID\>\`?fields=id,status,status\_code,video\_status" \\  
    \-H "Authorization: OAuth \<ACCESS\_TOKEN\>"  
Sample Response from the graph.facebook.com endpoint  
// A successfully created ig container  
{  
  "id": "\<IG\_MEDIA\_CONTAINER\_ID\>\`",  
  "status": "Published: Media has been successfully published.",  
  "status\_code": "PUBLISHED",  
  "video\_status": {  
    "uploading\_phase": {  
      "status": "complete",  
      "bytes\_transferred": 37006904  
    },  
    "processing\_phase": {  
      "status": "complete"  
    }  
  }  
}

// An interrupted ig container creation, from here you can resume your upload in step 2 with offset=50002.   
{  
  "id": "\<IG\_MEDIA\_CONTAINER\_ID\>\`",  
  "status": "Published: Media has been successfully published.",  
  "status\_code": "PUBLISHED",  
  "video\_status": {  
    "uploading\_phase": {  
      "status": "in\_progress",  
      "bytes\_transferred": 50002  
    },  
    "processing\_phase": {  
      "status": "not\_started"  
    }  
  }  
}  
"  
&  
"  
Comment Moderation  
This guide shows you how to get comments, reply to comments, delete comments, hide/unhide comments, and disable/enable comments on Instagram Media owned by your app users using the Instagram Platform.

In this guide we will be using Instagram user and Instagram professional account interchangeably. An Instagram User object represents your app user's Instagram professional account.

Requirements  
This guide assumes you have read the Instagram Platform Overview and implemented the needed components for using this API, such as a Meta login flow and a webhooks server to receive notifications.

You will need the following:

Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook Page access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_manage\_comments  
instagram\_basic  
instagram\_manage\_comments  
pages\_read\_engagement  
If the app user was granted a role on the Page connected to your app user's Instagram professional account via the Business Manager, your app will also need:

ads\_management  
ads\_read  
Webhooks

comments  
live\_comments  
comments  
live\_comments  
Access Level  
Advanced Access if your app serves Instagram professional accounts you don't own or manage  
Standard Access if your app serves Instagram professional accounts you own or manage and have added to your app in the App Dashboard  
Endpoints  
GET /\<IG\_MEDIA\_ID\>/comments — Get comments on an IG Media  
GET /\<IG\_COMMENT\_ID\>/replies — Get replies on an IG Comment  
POST /\<IG\_COMMENT\_ID\>/replies — Reply to an IG Comment  
POST /\<IG\_COMMENT\_ID\> — Hide/unhide an IG Comment  
POST /\<IG\_MEDIA\_ID\> — Disable/enable comments on an IG Media  
DELETE /\<IG\_COMMENT\_ID\> — Delete an IG Comment  
Get comments  
There are two ways to get comments on published Instagram media, an API query or a webhook notification. We strongly recommend using webhooks to prevent rate limiting.

API Request  
To get all the comments on a published Instagram media object, send a GET request to the /\<IG\_MEDIA\_ID\>/comments endpoint.

curl \-X GET "https://\<HOST\_URL\>/v24.0/\<IG\_MEDIA\_ID\>/comments"  
On success your app receives a JSON response with an array of objects containing the comment ID, the comment text, and the time the comment was published.

{  
  "data": \[  
    {  
      "timestamp": "2017-08-31T19:16:02+0000",  
      "text": "This is awesome\!",  
      "id": "17870913679156914"  
    },  
    {  
      "timestamp": "2017-08-31T19:16:02+0000",  
      "text": "Amazing\!",  
      "id": "17870913679156914"  
    },  
		... // results truncated for brevity  
  \]  
}  
Webhooks  
When the comments or live\_comments event is triggered your webhooks server receives a notification that includes the ID for your app user's published media, and the ID for the comments on that media, and the Instagram-scoped ID for the person who published the comment.

Note: When hosting an Instagram Live story, make sure your server can handle the increased load of notifications triggered by live\_comments webhooks events and that your system can differentiate between live\_comments and comments notifications.

Facebook Login for Business  
The following payload is returned for apps that have implemented Facebook Login for Business.

\[  
  {  
    "object": "instagram",  
    "entry": \[  
      {  
        "id": "\<YOUR\_APP\_USERS\_INSTAGRAM\_ACCOUNT\_ID\>",      // ID of your app user's Instagram professional account  
        "time": \<TIME\_META\_SENT\_THIS\_NOTIFICATION\>          // Time Meta sent the notification  
        "changes": \[  
          {  
            "field": "comments",  
            "value": {  
              "from": {  
                "id": "\<INSTAGRAM\_USER\_SCOPED\_ID\>",         // Instagram-scoped ID of the Instagram user who made the comment  
                "username": "\<INSTAGRAM\_USER\_USERNAME\>"     // Username of the Instagram user who made the comment  
              }',  
              "comment\_id": "\<COMMENT\_ID\>",                 // Comment ID of the comment with the mention  
              "parent\_id": "\<PARENT\_COMMENT\_ID\>",           // Parent comment ID, included if the comment was made on a comment  
              "text": "\<TEXT\_ID\>",                          // Comment text, included if comment included text  
              "media": {                                         
                "id": "\<MEDIA\_ID\>",                             // Media's ID that was commented on  
                "ad\_id": "\<AD\_ID\>",                             // Ad's ID, included if the comment was on an ad post  
                "ad\_title": "\<AD\_TITLE\_ID\>",                    // Ad's title, included if the comment was on an ad post  
                "original\_media\_id": "\<ORIGINAL\_MEDIA\_ID\>",     // Original media's ID, included if the comment was on an ad post  
                "media\_product\_type": "\<MEDIA\_PRODUCT\_ID\>"      // Product ID, included if the comment was on a specific product in an ad  
              }  
            }  
          }  
        \]  
      }  
    \]  
  }  
\]  
Business Login for Instagram  
The following payload is returned for apps that have implemented Business Login for Instagram.

\[  
  {  
    "object": "instagram",  
    "entry": \[  
      {  
        "id": "\<YOUR\_APP\_USERS\_INSTAGRAM\_ACCOUNT\_ID\>",  
        "time": \<TIME\_META\_SENT\_THIS\_NOTIFICATION\>

    // Comment or live comment payload  
        "field": "comments",  
        "value": {  
          "id": "\<COMMENT\_ID\>",  
          "from": {  
            "id": "\<INSTAGRAM\_SCOPED\_USER\_ID\>",  
            "username": "\<USERNAME\>"  
          },  
          "text": "\<COMMENT\_TEXT\>",  
          "media": {  
            "id": "\<MEDIA\_ID\>",  
            "media\_product\_type": "\<MEDIA\_PRODUCT\_TYPE\>"  
          }  
        }  
      }  
    \]  
  }  
\]  
Your app can parse the API or webhook notification for comments that match your app user's criteria then use the comment's ID to reply to that comment.

Reply to a comment  
To reply to a comment, send a POST request to the /\<IG\_COMMENT\_ID\>/replies endpoint, where \<IG\_COMMENT\_ID\> is the ID for the comment which you want to reply, with the message parameter set to your message text.

Sample Request  
curl \-X POST "https://\<HOST\_URL\>/v24.0/\<IG\_COMMENT\_ID\>/replies"  
   \-H "Content-Type: application/json"   
   \-d '{  
         "message":"Thanks for sharing\!"  
       }'  
On success, your app receives a JSON response with the comment ID for your comment.

{  
  "id": "17873440459141029"  
}  
If your app user has a lot of comments to reply to, you could batch the replies into a single request.

"  
&  
"  
Send a Private Reply to a Commenter  
This documents shows you how to programmatically send a private reply to a person who commented on your app user's Instagram professional post, reel, story, Live, or ad post.

How It Works  
Step 1\. An Instagram user comments on your app user's Instagram professional post, reel, story, Live, or ad post.

Step 2\. A webhook event is triggered and Meta sends your server a notification with information about the comment including:

Your app user's Instagram professional account ID  
The commenter's Instagram-scoped ID and username  
The comment's ID  
The media's ID, if the commenter included media in their comment  
The text of the comment, if applicable  
Step 3\. Your app uses the comment's ID to send a private response directly to the Instagram user. This reply appears in the person's Inbox, if the person follows the Instagram professional account, or to the Request folder, if they do not.

Step 4\. Your app can send this private reply within 7 days of the creation time of the comment, excepting Instagram Live, where replies can only be sent during the live broadcast. The private reply message includes a link to the commented post.

Requirements  
This guide assumes you have read the Instagram Platform Overview and implemented the needed components for using this API, such as a Meta login flow and a webhooks server to receive notifications.

You need the following:

Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook Page access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_manage\_comments  
instagram\_basic  
instagram\_manage\_comments  
pages\_read\_engagement  
If the app user was granted a role on the Page connected to your app user's Instagram professional account via the Business Manager, your app will also need:

ads\_management  
ads\_read  
Webhooks

comments  
live\_comments  
comments  
live\_comments  
Limitations  
Only one message can be sent to the commenter  
The message must be sent within 7 days of the comment was made on the post or reel  
For Instagram Live, private replies can only be sent during the live broadcast. Once the broadcast ends, private replies cannot be sent  
Follow-up messages can only be sent if the recipient responds, and must be sent within 24 hours of the response  
Send a Private Reply  
To send a private reply to a commenter on your app user's Instagram professional post, reel, or story, send a POST request to the \<APP\_USERS\_IG\_ID\>/messages endpoint. The recipient parameter should contain the comment's ID and the message parameter should contain the text you wish to send.

Sample request  
Formatted for readability.  
curl \-i \-X POST "https://\<HOST\_URL\>/\<API\_VERSION\>/\<APP\_USERS\_IG\_ID\>/messages"  
     \-H "Content-Type: application/json"   
     \-H "Authorization: Bearer \<ACCESS\_TOKEN\>"   
     \-d '{  
             "recipient":{   
                 "comment\_id": "\<COMMENT\_ID\>"   
             },  
             "message": {   
                 "text": "\<COMMENT\_TEXT\>"   
             }  
         }'  
On success, your app receives a JSON response with the recipient's Instagram-scoped ID and the ID for the message.

{  
  "recipient\_id": "526...",   // The Instagram-scoped ID   
  "message\_id": "aWdfZ..."    // The ID for the private reply message  
}  
"  
&  
"  
Insights  
This guide shows you how to get insights for your app users' Instagram media and professional accounts using the Instagram Platform.

In this guide we will be using Instagram user and Instagram professional account interchangeably. An Instagram User object represents your app user's Instagram professional account.

Instagram Insights are now available for Instagram API with Instagram Login. Learn more.

Before you start  
You will need the following:

Requirements  
This guide assumes you have read the Instagram Platform Overview and implemented the needed components for using this API, such as a Meta login flow and a webhooks server to receive notifications.

Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_manage\_insights  
instagram\_basic  
instagram\_manage\_insights  
pages\_read\_engagement  
If the app user was granted a role on the Page connected to your app user's Instagram professional account via the Business Manager, your app will also need:

ads\_management  
ads\_read  
Access Level  
Advanced Access if your app serves Instagram professional accounts you don't own or manage  
Standard Access if your app serves Instagram professional accounts you own or manage and have added to your app in the App Dashboard  
Endpoints  
GET /\<INSTAGRAM\_MEDIA\_ID\>/insights — Gets metrics on a media object  
GET /\<INSTAGRAM\_ACCOUNT\_ID\>/insights — Gets metrics on an Instagram Business Account or Instagram Creator account.  
Refer to each endpoint's reference documentation for additional metrics, parameters, and permission requirements.

UTC  
Timestamps in API responses use UTC with zero offset and are formatted using ISO-8601. For example: 2019-04-05T07:56:32+0000

Webhook event subscriptions  
story\_insights  – Only available for Instagram API with Facebook Login.  
Limitations  
Media insights  
Fields that return aggregated values don't include ads-driven data. For example, comments\_count returns the number of comments on a photo, but not comments on ads that contain that photo.  
Captions don't include the @ symbol unless the app user is also able to perform admin-equivalent tasks on the app.  
Some fields, such as permalink, cannot be used on photos within albums (children).  
Live video Instagram Media can only be read while they are being broadcast.  
This API returns only data for media owned by Instagram professional accounts. It can not be used to get data for media owned by personal Instagram accounts.  
Account insights  
Some metrics are not available on Instagram accounts with fewer than 100 followers.  
User Metrics data is stored for up to 90 days.  
You can only get insights for a single user at a time.  
You cannot get insights for Facebook Pages.  
If insights data you are requesting does not exist or is currently unavailable the API will return an empty data set instead of 0 for individual metrics.  
Examples  
Instagram account request  
The following Instagram API with Facebook Login example is getting the number of impressions, profile\_views, and reach for your app user's Instagram professional account over one 24 hour period.

To get metrics for an Instagram business or creator account, query the GET /\<INSTAGRAM\_USER\_ID\>/insights endpoint with the metrics parameter set to a comma-separated list of the metrics, impressions, profile\_views, and reach, and the period set to day.

GET graph.facebook.com/17841405822304914/insights  
    ?metric=impressions,reach,profile\_views  
    \&period=day  
Sample Response  
On success, your app receives an array for each metric that includes, the metric description, ID of the metric, name and title, the time period over which the metric was measured, and values of the metric.

{  
  "data": \[  
    {  
      "name": "impressions",  
      "period": "day",  
      "values": \[  
        {  
          "value": 32,  
          "end\_time": "2018-01-11T08:00:00+0000"  
        },  
        {  
          "value": 32,  
          "end\_time": "2018-01-12T08:00:00+0000"  
        }  
      \],  
      "title": "Impressions",  
      "description": "Total number of times the Business Account's media objects have been viewed",  
      "id": "instagram\_business\_account\_id/insights/impressions/day"  
    },  
    {  
      "name": "reach",  
      "period": "day",  
      "values": \[  
        {  
          "value": 12,  
          "end\_time": "2018-01-11T08:00:00+0000"  
        },  
        {  
          "value": 12,  
          "end\_time": "2018-01-12T08:00:00+0000"  
        }  
      \],  
      "title": "Reach",  
      "description": "Total number of times the Business Account's media objects have been uniquely viewed",  
      "id": "instagram\_business\_account\_id/insights/reach/day"  
    },  
    {  
      "name": "profile\_views",  
      "period": "day",  
      "values": \[  
        {  
          "value": 15,  
          "end\_time": "2018-01-11T08:00:00+0000"  
        },  
        {  
          "value": 15,  
          "end\_time": "2018-01-12T08:00:00+0000"  
        }  
      \],  
      "title": "Profile Views",  
      "description": "Total number of users who have viewed the Business Account's profile within the specified period",  
      "id": "instagram\_business\_account\_id/insights/profile\_views/day"  
    }  
  \]  
}  
Instagram media request  
The following Instagram API with Instagram Login example is getting the number of engagement, impressions, and reach for your app user's Instagram media over one 24 hour period.

To get metrics for an Instagram business or creator account's media, query the GET /\<INSTAGRAM\_MEDIA\_ID\>/insights endpoint with the metrics parameter set to a comma-separated list of the metrics, engagement, impressions, and reach, and the period set to day.

GET graph.instagram.com/17841491440582230/insights  
    ?metric=engagement,impressions,reach  
Sample Response  
On success, your app receives an array for each metric that includes, the metric description, ID of the metric, name and title, the time period over which the metric was measured, and values of the metric.

{  
  "data": \[  
    {  
      "name": "engagement",  
      "period": "lifetime",  
      "values": \[  
        {  
          "value": 8  
        }  
      \],  
      "title": "Engagement",  
      "description": "Total number of likes and comments on the media object",  
      "id": "media\_id/insights/engagement/lifetime"  
    },  
    {  
      "name": "impressions",  
      "period": "lifetime",  
      "values": \[  
        {  
          "value": 13  
        }  
      \],  
      "title": "Impressions",  
      "description": "Total number of times the media object has been seen",  
      "id": "media\_id/insights/impressions/lifetime"  
    },  
    {  
      "name": "reach",  
      "period": "lifetime",  
      "values": \[  
        {  
          "value": 13  
        }  
      \],  
      "title": "Reach",  
      "description": "Total number of unique accounts that have seen the media object",  
      "id": "media\_id/insights/reach/lifetime"  
    }  
  \]  
}  
"  
&  
"  
Sharing to Feed  
With Sharing to Feed, you can allow your app's Users to share your content to their Instagram Feed.

Overview  
By using Android Implicit Intents and iOS Universal Links or Document Interaction, your app can pass photos and videos to the Instagram app. The Instagram app will receive this content and load it in the feed composer so the User can publish it to their Instagram Feed.

Android Developers  
Android implementations use implicit intents with the EXTRA\_STREAM extra to prompt the User to select the Instagram app. Once selected, the intent will launch the Instagram app and pass it your content, which the Instagram App will then load in the Feed Composer.

In general, your sharing flow should:

Instantiate an implicit intent with the content you want to pass to the Instagram app.  
Start an activity and check that it can resolve the implicit intent.  
Resolve the activity if it is able to.  
Shareable Content  
You can pass the following content to the Instagram app:

Content	File Types	Description  
Image asset

JPEG, GIF, or PNG

\-

File asset

MKV, MP4

Minimum duration: 3 seconds Maximum duration: 10 minutes Minimum dimentions: 640x640 pixels

Sharing an Image Asset  
String type \= "image/\*";  
String filename \= "/myPhoto.jpg";  
String mediaPath \= Environment.getExternalStorageDirectory() \+ filename;

createInstagramIntent(type, mediaPath);

private void createInstagramIntent(String type, String mediaPath){

    // Create the new Intent using the 'Send' action.  
    Intent share \= new Intent(Intent.ACTION\_SEND);

    // Set the MIME type  
    share.setType(type);

    // Create the URI from the media  
    File media \= new File(mediaPath);  
    Uri uri \= Uri.fromFile(media);

    // Add the URI to the Intent.  
    share.putExtra(Intent.EXTRA\_STREAM, uri);

    // Broadcast the Intent.  
    startActivity(Intent.createChooser(share, "Share to"));  
}  
Sharing a Video Asset  
String type \= "video/\*";  
String filename \= "/myVideo.mp4";  
String mediaPath \= Environment.getExternalStorageDirectory() \+ filename;

createInstagramIntent(type, mediaPath);

private void createInstagramIntent(String type, String mediaPath){

    // Create the new Intent using the 'Send' action.  
    Intent share \= new Intent(Intent.ACTION\_SEND);

    // Set the MIME type  
    share.setType(type);

    // Create the URI from the media  
    File media \= new File(mediaPath);  
    Uri uri \= Uri.fromFile(media);

    // Add the URI to the Intent.  
    share.putExtra(Intent.EXTRA\_STREAM, uri);

    // Broadcast the Intent.  
    startActivity(Intent.createChooser(share, "Share to"));  
}  
iOS Developers  
iOS implementations can use universal links to launch the Instagram app and pass it content, or have it perform a specific action.

Universal Links  
Use the universal links listed in the following table to perform actions in the Instagram app.

Universal link	Action  
https://www.instagram.com

Launch the Instagram app.

https://www.instagram.com/create/story

Launch the Instagram app with the camera view or photo library on non-camera devices.

https://www.instagram.com/p/{media\_id}

Launch the Instagram app and load the post that matches the specified ID value (int).

https://www.instagram.com/{username}

Launch the Instagram app and load the Instagram user that matches the specified username value (string).

https://www.instagram.com/explore/locations/{location\_id}

Launch the Instagram app and load the location feed that matches the specified ID value (int).

https://www.instagram.com/explore/tags/{tag\_name}

Launch the Instagram app and load the page for the hashtag that matches the specified name value (string).

Sample Objective-C Code  
The following example in Objective-C launches the Instagram app with the camera view.

NSURL \*instagramURL \= \[NSURL URLWithString:@"https://www.instagram.com/create/story"\];  
if (\[\[UIApplication sharedApplication\] canOpenURL:instagramURL\]) {  
    \[\[UIApplication sharedApplication\] openURL:instagramURL\];  
}  
Document Interaction  
If your application creates photos and you'd like your users to share these photos using Instagram, you can use the Document Interaction API to open your photo in Instagram's sharing flow.

You must first save your file in PNG or JPEG (preferred) format and use the filename extension .ig. Using the iOS Document Interaction APIs you can trigger the photo to be opened by Instagram. The Identifier for our Document Interaction UTI is com.instagram.photo, and it conforms to the public/jpeg and public/png UTIs. See the Apple documentation articles: Previewing and Opening Files and the UIDocumentInteractionController Class Reference for more information.

Alternatively, if you want to show only Instagram in the application list (instead of Instagram plus any other public/jpeg-conforming apps) you can specify the extension class igo, which is of type com.instagram.exclusivegram.

When triggered, Instagram will immediately present the user with our filter screen. The image is preloaded and sized appropriately for Instagram. For best results, Instagram prefers opening a JPEG that is 640px by 640px square. If the image is larger, it will be resized dynamically.

"  
&  
"  
Sharing to Stories  
You can integrate sharing into your Android and iOS apps so that users can share your content as an Instagram story. To create a new app, see Getting Started with the Facebook SDK for Android and Getting Started with the Facebook SDK for iOS.

Beginning in January 2023, you must provide a Facebook AppID to share content to Instagram Stories. For more information, see Introducing an important update to Instagram Sharing to Stories. If you don't provide an AppID, your users see the error message "The app you shared from doesn't currently support sharing to Stories" when they attempt to share their content to Instagram. To find your App ID, see Get Your App ID (Android) and Get Your App ID (iOS).

Overview  
By using Android Implicit Intents and iOS Custom URL Schemes, your app can send photos, videos, and stickers to the Instagram app. The Instagram app receives this content and load it in the story composer so the User can publish it to their Instagram Stories.

	  
The Instagram app's story composer is comprised of a background layer and a sticker layer.

Background Layer  
The background layer fills the screen and you can customize it with a photo, video, solid color, or color gradient.

Sticker Layer  
The sticker layer can contain an image, and the layer can be further customized by the User within the story composer.

Android Developers  
Android implementations use implicit intents to launch the Instagram app and pass it content. In general, your sharing flow should:

Instantiate an implicit intent with the content you want to pass to the Instagram app.  
Start an activity and check that it can resolve the implicit intent.  
Resolve the activity if it is able to.  
Data  
You send the following data when you share to Stories.

Content	Type	Description  
Facebook App ID

String

Your Facebook App ID.

Background asset

Uri

Uri to an image asset (JPG, PNG) or video asset (H.264, H.265, WebM). Minimum dimensions 720x1280. Recommended image ratios 9:16 or 9:18. Videos can be 1080p and up to 20 seconds in duration. The Uri needs to be a content Uri to a local file on the device. You must send a background asset, a sticker asset, or both.

Sticker asset

Uri

Uri to an image asset (JPG, PNG). Recommended dimensions: 640x480. This image appears as a sticker over the background. The Uri needs to be a content Uri to a local file on the device. You must send a background asset, a sticker asset, or both.

Background layer top color

String

A hex string color value used in conjunction with the background layer bottom color value. If both values are the same, the background layer is a solid color. If they differ, they are used to generate a gradient. If you specify a background asset, the asset is used and this value is ignored.

Background layer bottom color

String

A hex string color value used in conjunction with the background layer top color value. If both values are the same, the background layer is a solid color. If they differ, they are used to generate a gradient. If you specify a background asset, the asset is used and this value is ignored.

Sharing a Background Asset  
The following code example sends an image to Instagram so the user can publish it to their Instagram Stories.

// Instantiate an intent  
Intent intent \= new Intent("com.instagram.share.ADD\_TO\_STORY");

// Attach your App ID to the intent  
String sourceApplication \= "1234567"; // This is your application's FB ID  
intent.putExtra("source\_application", sourceApplication);

// Attach your image to the intent from a URI  
Uri backgroundAssetUri \= Uri.parse("your-image-asset-uri-goes-here");  
intent.setDataAndType(backgroundAssetUri, MEDIA\_TYPE\_JPEG);

// Grant URI permissions for the image  
intent.setFlags(Intent.FLAG\_GRANT\_READ\_URI\_PERMISSION);

// Instantiate an activity  
Activity activity \= getActivity();

// Verify that the activity resolves the intent and start it  
if (activity.getPackageManager().resolveActivity(intent, 0\) \!= null) {  
  activity.startActivityForResult(intent, 0);  
}    
Sharing a Sticker Asset  
This example sends a sticker layer image asset and a set of background layer colors to Instagram. If you don't specify the background layer colors, the background layer color is \#222222.

// Instantiate an intent  
Intent intent \= new Intent("com.instagram.share.ADD\_TO\_STORY");

// Attach your App ID to the intent  
String sourceApplication \= "1234567"; // This is your application's FB ID  
intent.putExtra("source\_application", sourceApplication);

// Attach your sticker to the intent from a URI, and set background colors  
Uri stickerAssetUri \= Uri.parse("your-image-asset-uri-goes-here");  
intent.setType(MEDIA\_TYPE\_JPEG);  
intent.putExtra("interactive\_asset\_uri", stickerAssetUri);  
intent.putExtra("top\_background\_color", "\#33FF33");  
intent.putExtra("bottom\_background\_color", "\#FF00FF");

// Instantiate an activity  
Activity activity \= getActivity();

// Grant URI permissions for the sticker  
activity.grantUriPermission(  
    "com.instagram.android", stickerAssetUri, Intent.FLAG\_GRANT\_READ\_URI\_PERMISSION);

// Verify that the activity resolves the intent and start it  
if (activity.getPackageManager().resolveActivity(intent, 0\) \!= null) {  
  activity.startActivityForResult(intent, 0);  
}  
Sharing a Background Asset and a Sticker Asset  
This example sends a background layer image asset and a sticker layer image asset to Instagram.

// Instantiate an intent  
Intent intent \= new Intent("com.instagram.share.ADD\_TO\_STORY");

// Attach your App ID to the intent  
String sourceApplication \= "1234567"; // This is your application's FB ID  
intent.putExtra("source\_application", sourceApplication);

// Attach your image to the intent from a URI  
Uri backgroundAssetUri \= Uri.parse("your-background-image-asset-uri-goes-here");  
intent.setDataAndType(backgroundAssetUri, MEDIA\_TYPE\_JPEG);

// Attach your sticker to the intent from a URI  
Uri stickerAssetUri \= Uri.parse("your-sticker-image-asset-uri-goes-here");  
intent.putExtra("interactive\_asset\_uri", stickerAssetUri);

// Grant URI permissions for the image  
intent.setFlags(Intent.FLAG\_GRANT\_READ\_URI\_PERMISSION);

// Instantiate an activity  
Activity activity \= getActivity();

// Grant URI permissions for the sticker  
activity.grantUriPermission(  
    "com.instagram.android", stickerAssetUri, Intent.FLAG\_GRANT\_READ\_URI\_PERMISSION);

// Verify that the activity resolves the intent and start it  
if (activity.getPackageManager().resolveActivity(intent, 0\) \!= null) {  
  activity.startActivityForResult(intent, 0);  
}  
iOS Developers  
iOS implementations use a custom URL scheme to launch the Instagram app and pass it content. In general, your sharing flow should:

Check that your app can resolve Instagram's custom URL scheme.  
Assign the content that you want to share to the pasteboard.  
Resolve the custom URL scheme if your app is able to.  
Data  
You send the following data when you share to Stories.

Content	Type	Description  
Facebook App ID

NSString \*

Your Facebook App ID.

Background image asset

NSData \*

Data for an image asset in a supported format (JPG, PNG). Minimum dimensions 720x1280. Recommended image ratios 9:16 or 9:18. You must pass the Instagram app a background asset (image or video), a sticker asset, or both.

Background video asset

NSData \*

Data for video asset in a supported format (H.264, H.265, WebM). Videos can be 1080p and up to 20 seconds in duration. Under 50 MB recommended. You must pass the Instagram app a background asset (image or video), a sticker asset, or both.

Sticker asset

NSData \*

Data for an image asset in a supported format (JPG, PNG). Recommended dimensions: 640x480. This image appears as a sticker over the background. You must pass the Instagram app a background asset (image or video), a sticker asset, or both.

Background layer top color

NSString \*

A hex string color value used in conjunction with the background layer bottom color value. If both values are the same, the background layer is a solid color. If they differ, they are used to generate a gradient.

Background layer bottom color

NSString \*

A hex string color value used in conjunction with the background layer bottom color value. If both values are the same, the background layer is a solid color. If they differ, they are used to generate a gradient.

Register Instagram's Custom URL Scheme  
You need to register Instagram's custom URL scheme before your app use it. Add instagram-stories to the LSApplicationQueriesSchemes key in your app's Info.plist.

Sharing a Background Asset  
The following code example sends a background layer image asset to Instagram so the user can edit and publish it to their Instagram Stories.

\- (void)shareBackgroundImage  
{  
  // Identify your App ID  
  NSString \*const appIDString \= @"1234567890";

  // Call method to share image  
  \[self backgroundImage:UIImagePNGRepresentation(\[UIImage imageNamed:@"backgroundImage"\])   
        appID:appIDString\];  
}

// Method to share image  
\- (void)backgroundImage:(NSData \*)backgroundImage   
        appID:(NSString \*)appID  
{  
  NSURL \*urlScheme \= \[NSURL URLWithString:\[NSString stringWithFormat:@"instagram-stories://share?source\_application=%@", appID\]\];

  if (\[\[UIApplication sharedApplication\] canOpenURL:urlScheme\])  
  {  
    // Attach the pasteboard items  
    NSArray \*pasteboardItems \= @\[@{@"com.instagram.sharedSticker.backgroundImage" : backgroundImage}\];

    // Set pasteboard options  
    NSDictionary \*pasteboardOptions \= @{UIPasteboardOptionExpirationDate : \[\[NSDate date\] dateByAddingTimeInterval:60 \* 5\]};

    // This call is iOS 10+, can use 'setItems' depending on what versions you support  
    \[\[UIPasteboard generalPasteboard\] setItems:pasteboardItems options:pasteboardOptions\];  
      
    \[\[UIApplication sharedApplication\] openURL:urlScheme options:@{} completionHandler:nil\];  
  }   
  else  
  {  
      // Handle error cases  
  }  
}   
Sharing a Sticker Asset  
This sample code shows how to pass the Instagram app a sticker layer image asset and a set of background layer colors. If you don't specify the background layer colors, the background layer color is \#222222.

\- (void)shareStickerImage  
{  
  // Identify your App ID  
  NSString \*const appIDString \= @"1234567890";

  // Call method to share sticker  
  \[self stickerImage:UIImagePNGRepresentation(\[UIImage imageNamed:@"stickerImage"\])  
        backgroundTopColor:@"\#444444"  
        backgroundBottomColor:@"\#333333"  
        appID:appIDString\];  
}

// Method to share sticker  
\- (void)stickerImage:(NSData \*)stickerImage   
        backgroundTopColor:(NSString \*)backgroundTopColor   
        backgroundBottomColor:(NSString \*)backgroundBottomColor  
        appID:(NSString \*)appID  
{  
  NSURL \*urlScheme \= \[NSURL URLWithString:\[NSString stringWithFormat:@"instagram-stories://share?source\_application=%@", appID\]\];

  if (\[\[UIApplication sharedApplication\] canOpenURL:urlScheme\])  
  {  
    // Attach the pasteboard items  
    NSArray \*pasteboardItems \= @\[@{@"com.instagram.sharedSticker.stickerImage" : stickerImage,  
                                   @"com.instagram.sharedSticker.backgroundTopColor" : backgroundTopColor,  
                                   @"com.instagram.sharedSticker.backgroundBottomColor" : backgroundBottomColor}\];

    // Set pasteboard options  
    NSDictionary \*pasteboardOptions \= @{UIPasteboardOptionExpirationDate : \[\[NSDate date\] dateByAddingTimeInterval:60 \* 5\]};

    // This call is iOS 10+, can use 'setItems' depending on what versions you support  
    \[\[UIPasteboard generalPasteboard\] setItems:pasteboardItems options:pasteboardOptions\];

    \[\[UIApplication sharedApplication\] openURL:urlScheme options:@{} completionHandler:nil\];  
  }   
  else  
  {  
      // Handle error cases  
  }  
}  
Sharing a Background Asset and Sticker Asset  
This sample code shows how to pass the Instagram app a background layer image asset and a sticker layer image asset.

\- (void)shareBackgroundAndStickerImage  
{  
  // Identify your App ID  
  NSString \*const appIDString \= @"1234567890";

  // Call method to share image and sticker  
  \[self backgroundImage:UIImagePNGRepresentation(\[UIImage imageNamed:@"backgroundImage"\])  
        stickerImage:UIImagePNGRepresentation(\[UIImage imageNamed:@"stickerImage"\])  
        appID:appIDString\];  
}

// Method to share image and sticker  
\- (void)backgroundImage:(NSData \*)backgroundImage   
        stickerImage:(NSData \*)stickerImage   
        appID:(NSString \*)appID  
{  
  NSURL \*urlScheme \= \[NSURL URLWithString:\[NSString stringWithFormat:@"instagram-stories://share?source\_application=%@", appID\]\];

  if (\[\[UIApplication sharedApplication\] canOpenURL:urlScheme\])  
  {  
    // Attach the pasteboard items  
    NSArray \*pasteboardItems \= @\[@{@"com.instagram.sharedSticker.backgroundImage" : backgroundImage,  
                                   @"com.instagram.sharedSticker.stickerImage" : stickerImage}\];

    // Set pasteboard options  
    NSDictionary \*pasteboardOptions \= @{UIPasteboardOptionExpirationDate : \[\[NSDate date\] dateByAddingTimeInterval:60 \* 5\]};

    // This call is iOS 10+, can use 'setItems' depending on what versions you support  
    \[\[UIPasteboard generalPasteboard\] setItems:pasteboardItems options:pasteboardOptions\];

    \[\[UIApplication sharedApplication\] openURL:urlScheme options:@{} completionHandler:nil\];  
  }  
  else  
  {  
      // Handle error cases  
  }  
}  
"  
&  
"  
Embed an Instagram Post  
You can query the Instagram oEmbed endpoint to get an Instagram post’s embed HTML and basic metadata in order to display the post in another website or app. Supports photo, video, Reel, and Feed posts.

Visit the Instagram Help Center to learn how to get the embed code from a public Instagram post or profile.

On April 8, 2025, we introduced a new oEmbed feature, Meta oEmbed Read to replace the existing oEmbed Read feature. The current oEmbed Read feature will be deprecated on November 3, 2025\.

Apps created after April 8, 2025 that implement oEmbed will use the new Meta oEmbed Read feature.  
Existing apps that already use the current oEmbed Read feature will be automatically updated to the new Meta oEmbed Read feature by November 3, 2025\.

The following fields are no longer returned and will be fully deprecated on November 3, 2025:

author\_name  
author\_url  
thumbnail\_height  
thumbnail\_url  
thumbnail\_width

Read the oEmbed Updates blog post from Meta to learn more.

Common uses  
Embed a post in a blog  
Embed a post in a website  
Render a post in a content management system  
Render a post in a messaging app  
Requirements  
This guide assumes you are a registered Meta developer  and have created a Meta app. 

You will need the following:

Access levels  
Advanced Accessfor the Meta oEmbed Read feature – Requires Meta App Review  
Access tokens  
An app access token,  if your app accesses the oEmbed endpoint from a backend server  
An client access token,  if your app accesses the oEmbed endpoint from a user agent, such as a mobile device or web browser  
Base URL  
All endpoints can be accessed via the graph.facebook.com host.

Endpoints  
GET /instagram\_oembed  
Features  
Meta oEmbed Read feature   
Limitations  
The Instagram oEmbed endpoint is only meant to be used for embedding Instagram content in websites and apps. It is not to be used for any other purpose. Using metadata and page, post, or video content (or their derivations) from the endpoint for any purpose other than providing a front-end view of the page, post, or video is strictly prohibited. This prohibition encompasses consuming, manipulating, extracting, or persisting the metadata and content, including but not limited to deriving information about pages, posts, and videos from the metadata for analytics purposes.  
Posts on private, inactive, and age-restricted Instagram accounts are not supported.  
Accounts that have disabled Embeds are not supported.  
Stories are not supported.  
Shadow DOM is not supported.  
Rate limits  
Rate limits are dependent on the type of access token your app includes in each request.

App token rate limits  
Apps that rely on app access tokens can make up to 5 million requests per 24 hours.

Client token rate limits  
Client token rate limits are significantly lower than app token rate limits. We do not reveal the actual limit as it will change depending on your app activity. However, you can safely assume that your app will not reach its limit unless it exhibits bot-like behavior, such as batching thousands of requests, or sending thousands of requests per agent or app user.

Get an embed HTML  
You can get an embed HTML programmatically or in the Instagram app. 

To programmatically get an Intagram post's embed HTML, send a request to:

GET /instagram\_oembed?url=\<URL\_OF\_THE\_POST\>\&access\_token=\<ACCESS\_TOKEN\>  
Replace \<URL\_OF\_THE\_POST\> with the URL of the Instagram post that you want to query and \<ACCESS\_TOKEN\> with your app or client access token or pass it to us in an Authorization HTTP header.

Authorization: Bearer \<ACCESS\_TOKEN\>

If you are using a client access token, remember that you must combine it with your Meta App ID using a pipe symbol otherwise the request will fail.

Upon success, the API will respond with a JSON object containing the post's embed HTML and additional data. The embed HTML will be assigned to the html property.

Refer to the Instagram oEmbed reference for a list of query string parameters you can include to augment the request. You may also include the fields query string parameter to specify which fields you want returned. If omitted, all default Fields will be included in the response.

Sample requests  
curl \-X GET \\  
  "https://graph.facebook.com/v24.0/instagram\_oembed?url=https://www.instagram.com/p/fA9uwTtkSN/\&access\_token=IGQVJ..."  
curl \-i \-X GET \\  
     \--header "Authorization: Bearer 96481..." \\  
     "https://graph.facebook.com/v24.0/instagram\_oembed?url=https%3A%2F%2Fwww.instagram.com%2Fp%2FfA9uwTtkSN"  
Sample Response  
Some values truncated with an ellipsis (...) for readability.

{  
  "version": "1.0",  
  "author\_name": "diegoquinteiro",  
  "provider\_name": "Instagram",  
  "provider\_url": "https://www.instagram.com/",  
  "type": "rich",  
  "width": 658,  
  "html": "\<blockquote class=\\"instagram-media\\" data-instgrm-ca...",  
  "thumbnail\_width": 640,  
  "thumbnail\_height": 640  
}  
URL Formats  
The url query string parameter accepts the following URL formats:

https://www.instagram.com/p/{media-shortcode}/  
https://www.instagram.com/tv/{media-shortcode}/ https://www.instagram.com/{username}/guide/{slug}/{guide\_id}

Embed JS  
The embed HTML contains a reference to the Instagram embed.js JavaScript library. When the library loads, it scans the page for the post HTML and generates the fully rendered post. If you want to load the library separately, include the omitscript=true query string parameter in your request. To manually initialize the embed HTML, call the instgrm.Embeds.process() function after loading the library.

Post Size  
The embedded post is responsive and will adapt to the size of its container. This means that the height will vary depending on the container width and the length of the caption. You can set the maximum width by including the maxwidth query string parameter in your request.

Get thumbnails  
We recommend that you render all of the post’s embed HTML whenever possible. If you are unable to do this, you can get a post’s thumbnail image URL and render that instead. If you do this, however, you must provide clear attribution next to the image, including attribution to the original author and to Instagram, and a link to the Instagram post that you are querying.

To get a post’s thumbnail URL and attribution information, send a request to:

GET /instagram\_oembed  
  ?url=\<URL\_OF\_THE\_POST\>  
  \&maxwidth=\<MAX\_WIDTH\>  
  \&fields=thumbnail\_url,author\_name,provider\_name,provider\_url  
  \&access\_token=\<ACCESS\_TOKEN\>  
Replace \<URL\_OF\_THE\_POST\> with the URL of the Instagram post you want to query, \<MAX\_WIDTH\> with the maximum size of the thumbnail you want to render, and \<ACCESS\_TOKEN\> with your app or client access token.

Sample request  
curl \-i \-X GET \\  
  "https://graph.facebook.com/v24.0/instagram\_oembed?url=https%3A%2F%2Fwww.instagram.com%2Fp%2FfA9uwTtkSN\&maxwidth=320\&fields=thumbnail\_url%2Cauthor\_name%2Cprovider\_name%2Cprovider\_url\&access\_token=96481..."  
Sample Response  
Some values truncated with an ellipsis (...) for readability.

{  
  "thumbnail\_url": "https://scontent.cdninstagram.com/v/t51.288...",  
  "author\_name": "diegoquinteiro",  
  "provider\_name": "Instagram",  
  "provider\_url": "https://www.instagram.com/"  
}  
App Review submission  
When you submit your app for review, in the Tell Us Why You're Requesting Oembed Read \> Please provide a URL where we can test Oembed Read form field, use the Instagram oEmbed endpoint to get the embed HTML for any public post on our official Facebook Page or Instagram Page. Then, add the returned embed HTML to where you will be displaying oEmbed content and enter that page's URL in the form field.

Once you have been approved for the oEmbed Read feature you may embed your own pages, posts, or videos using their respective URLs.  
"  
&  
"  
Error Codes  
This document describes the error messages that can be returned by the Instragram API. The sample response below shows an example of code 3600 and subcode 2207004 with the subsequent error codes defined.

Sample Response  
{  
  "error":   
    {  
      "message": "The image size is too large.",  
      "type": "OAuthException",  
      "code": 36000,  
      "error\_subcode": 2207004,  
      "is\_transient": false,  
      "error\_user\_title": "Image size too large",  
      "error\_user\_msg": "The image is too large to download. It should be less than 8 MiB.",  
      "fbtrace\_id": "A6LJylpZRKw2xKLFsAP-cJh"  
   }  
 }  
Error Codes Defined  
HTTP Status Code	Code	Subcode	User Message	Recommended Solution  
400

\-2

2207003

It takes too long to download the media.

A timeout occured while downloading the media. Try again.

400

\-2

2207020

The media you are trying to access has expired. Please try to upload again.

Generate a new container ID and use it to try again.

400

\-1

2207001

Instagram server error. Try again.

400

\-1

2207032

Create media fail, please try to re-create media

Failed to create a media container. Try again.

400

\-1

2207053

unknown upload error

An unknown error occured during upload. Generate a new container and use it to try again. This should only affect video uploads.

400

1

2207057

Thumbnail offset must be greater than or equal to 0 and less than video duration, i.e. {video-length}

The thumbnail offset you entered is out of bounds for the video duration. Add the right offset in milliseconds.

400

4

2207051

We restrict certain activity to protect our community. Tell us if you think we made a mistake.

The publishing action is suspected to be spam. We restrict certain activity to protect our community. Let us know if you can determine that the publishing actions is not spam.

400

9

2207042

You reached maximum number of posts that is allowed to be published by Content Publishing API.

The app user has reached their daily publishing limit. Advise the app's user to try again the following day.

400

24

2207006

The media with {media-id} cannot be found

Possible permission error due to missing permission or expired token. Generate a new container and use it to try again.

400

24

2207008

The media builder with creation id \= {creation-id} does not exist or has been expired.

Temporary error publishing a container. Try again 1–2 times in the next 30 seconds to 2 minutes. If unsuccessful, generate a new container ID and use it to try again.

400

25

2207050

The Instagram account is restricted.

The app user's Instagram Professional account is inactive, checkpointed, or restricted. Advise the app user to sign in to the Instagram app and complete any actions the app requires to re-enable their account.

400

100

2207023

The media type {media-type} is unknown.

The media type entered is not one of the expected media types. Please enter the correct one.

400

100

2207028

Your post won't work as a carousel. Carousels need at least 2 photos/videos and no more than 10 photos/videos.

Try again using an acceptable number of photos/videos.

400

100

2207035

Product tag positions should not be specified for video media.

Videos do not support X/Y coordinates. Disallow X/Y coordinates with videos.

400

100

2207036

Product tag positions are required for photo media.

Image product tags must include X/Y coordinates. Require X/Y coordinates for images.

400

100

2207037

We couldn't add all of your product tags. The product ID may be incorrect, the product may be deleted, or you may not have permission to tag the product.

One or more of the products being used to tag the item is invalid (deleted, rejected, app user lacks permission, product ID is invalid, etc.). Get the app user's catalogs and eligible products again and allow the app user to only use those product IDs when tagging.

400

100

2207040

Cannot use more than {max-tag-count} tags per created media.

The app user exceeded the maximum number (20) of @ tags. Advise user to use fewer @ tags.

400

352

2207026

The video format is not supported. Please check spec for supported {video} format

Unsupported video format. Advise the app user to upload an MOV or MP4 (MPEG-4 Part 14). See Video Specifications.

400

9004

2207052

The media could not be fetched from this uri: {uri}

The media could not be fetched from the supplied URI. Advise the app user to make sure the URI is valid and publicly available.

400

9007

2207027

The media is not ready for publishing, please wait for a moment

Check the container status and publish when its status is FINISHED.

400

36000

2207004

The image is too large to download. It should be less than {size}.

Image exceeded maximum file size of 8MiB. Advise the user to try again with a smaller image.

400

36001

2207005

The image format {current-image-format} is not supported. Supported formats are: {format}.

Possible permission error due to missing permission or expired token. Generate a new container and use it to try again.

400

36003

2207009

The submitted image with aspect ratio {submitted-ratio} cannot be published. Please submit an image with a valid aspect ratio.

The image's aspect ratio does not fall within our acceptable range. Advise the app user to try again with an image that falls withing a 4:5 to 1.91:1 range.

400

36004

2207010

The submitted image's caption was {submitted-caption-length} characters long. The maximum number of characters permitted for a caption is {maximum-caption-length}. Please submit media with a shorter caption.

The user exceeded the maximum amount of characters for a caption. Advise user to use a shorter caption. Maximum 2,200 characters, 30 hashtags, and 20 @ tags.

"  
&  
"  
Access Token  
The /access\_token endpoint allows you to exchange short-lived Instagram User Access Tokens, those that expire in one hour, for long-lived Instagram User access Tokens that expire in 60 days.

Creating  
This operation is not supported.

Reading  
GET /access\_token

Exchange a short-lived Instagram User access token, that expires in one hour, for long-lived Instagram User access token that expires in 60 days.

Limitations  
Requests for long-lived tokens include your app secret so should only be made in server-side code, never in client-side code or in an app binary that could be decompiled. Do not share your app secret with anyone, expose it in code, send it to a client, or store it in a device.

Requirements  
Access tokens  
An Instagram User access token requested from a person who can send a message from the Instagram professional account  
Base URL  
All endpoints can be accessed via the graph.instagram.com host.

Endpoints  
/access\_token  
Required Parameters  
The following table contains the required parameters for each API request.

Key	Value  
client\_secret  
Required  
String

Your Instagram app's secret, displayed in the App Dashboard \> Products \> Instagram \> Basic Display \> Instagram App Secret field.

grant\_type  
Required  
String

Set this to ig\_exchange\_token

access\_token  
Required  
String

The valid (unexpired) short-lived Instagram User Access Token that you want to exchange for a long-lived token.

Permissions  
instagram\_graph\_user\_profile for Instagram Basic Display API  
Request Syntax  
Formatted for readability.

GET https://graph.instagram.com/access\_token  
  ?grant\_type=ig\_exchange\_token  
  \&client\_secret=\<INSTAGRAM\_APP\_SECRET\>  
  \&access\_token=\<VALID\_SHORT\_LIVED\_ACCESS\_TOKEN\>  
Response  
Upon success, your app receives a JSON-formatted object containing the following:

access\_token set to the new, long-lived Instagram User access token; numeric string  
token\_type set to bearer; string  
expires\_in set to the number of seconds until the token expires; integer  
cURL Example  
Request  
curl \-X GET "https://graph.instagram.com/access\_token?grant\_type=ig\_exchange\_token&\&client\_secret=eb87G...\&access\_token=IGQVJ..."  
Response  
{  
  "access\_token": "lZAfb2dhVW...",  
  "token\_type": "bearer",  
  "expires\_in": 5184000  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.  
"  
&  
"  
Instagram (IG) Comment  
Represents a comment on an Instagram media object.

If you are migrating from Marketing API Instagram Ads endpoints to Instagram Platform endpoints, be aware that some field names are different.

Introducing the following fields:

legacy\_instagram\_comment\_id  
The following fields are not supported:

comment\_type  
mentioned\_instagram\_users  
Requirements  
Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_manage\_comments  
instagram\_basic  
instagram\_manage\_comments  
pages\_read\_engagement  
If the app user was granted a role via the Business Manager on the Page connected to the targeted IG User, you will also need one of:

ads\_management  
ads\_read  
Creating  
This operation is not supported.

Reading  
GET \<HOST\_URL\>/\<IG\_COMMENT\_ID\>?fields=\<LIST\_OF\_FIELDS\>

Get fields and edges on an IG Comment.

Limitations  
Requests cannot be performed on comments discovered through the Mentions API unless the request is made by the comment owner. Instead, use the Mentioned Comment node.  
Comments on age-gated media are not returned.  
Comments created by IG Users who have been restricted by the app user will not be returned unless the IG Users are unrestricted and the Comments are approved.  
Comments on live video IG Media can only be read while the IG Media upon which the comment was created is being broadcast.  
Request Syntax  
GET https://\<HOST\_URL\>/\<API\_VERSION\>/\<IG\_COMMENT\_ID\>  
  ?fields=\<LIST\_OF\_FIELDS\>  
  \&access\_token=\<ACCESS\_TOKEN\>  
Path Parameters  
Placeholder	Value  
\<API\_VERSION\>

API version.

\<HOST\_URL\>

API version.

\<IG\_COMMENT\_ID\>

Required. IG Comment ID.

Query String Parameters  
Key	Placeholder	Value  
access\_token

\<ACCESS\_TOKEN\>

Required. App user's User access token.

fields

\<LIST\_OF\_FIELDS\>

Comma-separated list of IG Comment fields you want returned for each IG Comment in the result set.

Fields  
Field Name	Description  
from

An object containing:

id — The Instagram-scoped ID (IGSID) of the Instagram user who created the IG Comment.  
username — Username of the Instagram user who created the IG Comment.  
hidden

Indicates if comment has been hidden (true) or not (false).

id

IG Comment ID.

like\_count

Number of likes on the IG Comment.

legacy\_instagram\_comment\_id

The ID for Instagram comment that was created for Marketing API endpoints for v21.0 and older.

media

An object containing:

id — ID of the IG Media upon which the IG Comment was made.  
media\_product\_type — Published surface of the IG Media (i.e. where the IG Media appears) upon which the IG Comment was made.  
parent\_id

ID of the parent IG Comment if this comment was created on another IG Comment (i.e. a reply to another comment.

replies

A list of replies (IG Comments) made on the IG Comment.

text

IG Comment text.

timestamp

ISO 8601 formatted timestamp indicating when IG Comment was created.

Example: 2017-05-19T23:27:28+0000.

user

ID of IG User who created the IG Comment. Only returned if the app user created the IG Comment, otherwise username will be returned instead.

username

Username of Instagram user who created the IG Comment.

Starting August 27, 2024, the instagram\_manage\_comments permission (if your app uses Facebook login) and instagram\_business\_manage\_comments permission (if your app uses Instagram login) will be required to access the username field of an Instagram user who commented on media of an app user's Instagram professional account.

Edges  
Edge	Description  
replies

Get a list of IG Comments on the IG Comment; Create an IG Comment on an IG Comment.

Response  
A JSON-formatted object containing default and requested fields and edges.

{  
  "\<FIELD\>":"\<VALUE\>",  
  ...  
}  
cURL Example  
Request  
curl \-i \-X GET \\  
 "https://graph.instagram.com/v24.0/17881770991003328?fields=hidden%2Cmedia%2Ctimestamp\&access\_token=EAAOc..."  
Response  
{  
  "hidden": false,  
  "media": {  
    "id": "17856134461174448"  
  },  
  "timestamp": "2017-05-19T23:27:28+0000",  
  "id": "17881770991003328"  
}  
Updating  
Hiding/Unhiding a Comment  
POST \<HOST\_URL\>/\<IG\_COMMENT\_ID\>?hide=\<BOOLEAN\>

Query String Parameters  
hide (required) — Set to true to hide the comment, or false to show the comment.  
Limitations  
Comments made by media object owners on their own media objects will always be displayed, even if the comments have been set to hide=true.  
Comments on live video IG Media are not supported.  
Access token  
A user access token from the user who owns the media object that was commented on.

Example Request  
Hiding a comment:

POST graph.instagram.com  
  /17873440459141021?hide=true  
Example Response  
{  
  "success": true  
}  
Deleting  
Deleting a Comment  
DELETE \<HOST\_URL\>/\<IG\_COMMENT\_ID\>

Access token  
A User access token from a User who created the comment.

Limitations  
A comment can only be deleted by the owner of the object upon which the comment was made, even if the user attempting to delete the comment is the comment's author.  
Comments on live video IG Media are not supported.  
Example Request  
DELETE graph.instagram.com  
  /17873440459141021  
Example Response  
{  
  "success": true  
}  
"  
&  
"  
IG Comment Replies  
Represents a collection of IG Comments on an IG Comment.

To create an IG Comment on an IG Media object, use the POST /{ig-media-id}/comments endpoint instead.

Creating  
Replying to a Comment  
POST /{ig-comment-id}/replies?message={message}

Creates an IG Comment on an IG Comment.

Query String Parameters  
Query string parameters are optional unless indicated as required.

{message} (required) — The text to be included in the comment.  
Limitations  
You can only reply to top-level comments; replies to a reply will be added to the top-level comment.  
You cannot reply to hidden comments.  
You cannot reply to comments on a live video; use the Instagram Messaging API to send a private reply instead.  
Permissions  
A User access token from a User who created the comment, with the following permissions:

instagram\_basic  
instagram\_manage\_comments  
pages\_show\_list  
page\_read\_engagement  
If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required:

ads\_management  
ads\_read  
Sample Request  
POST graph.facebook.com  
  /17870913679156914/replies?message=\*sniff\*  
Sample Response  
{  
  "id": "17873440459141021"  
}  
Reading  
Getting All Replies (Comments) on a Comment  
GET /{ig-comment-id}/replies

Returns a list of IG Comments on an IG Comment.

Limitations  
You cannot get replies to a comment that has been deleted.

Permissions  
An access token from a User who created the comment, with the following permissions:

instagram\_basic  
pages\_show\_list  
page\_read\_engagement  
If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required:

ads\_management  
ads\_read  
Sample Request  
GET graph.facebook.com  
  /17873440459141021/replies  
Sample Response  
{  
  "data": \[  
    {  
      "timestamp": "2017-08-31T16:53:49+0000",  
      "text": "This is a great comment",  
      "id": "17871618799146774"  
    },  
    {  
      "timestamp": "2017-08-30T04:24:45+0000",  
      "text": "It's me. Trust me.",  
      "id": "17887288333072596"  
    }  
  \]  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
Instagram (IG) Container  
Represents a media container for publishing an Instagram media object.

Requirements  
Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User user access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_content\_publish  
instagram\_basic  
instagram\_content\_publish  
pages\_read\_engagement  
If the app user was granted a role via the Business Manager on the Page connected to the targeted IG User, you will also need one of:

ads\_management  
ads\_read  
Creating  
This operation is not supported.

Reading  
GET \<HOST\_URL\>/\<IG\_CONTAINER\_ID\>

Get fields and edges on an IG Container.

Request Syntax  
GET \<HOST\_URL\>/\<API\_VERSION\>/\<IG\_CONTAINER\_ID\>  
  ?fields=\<LIST\_OF\_FIELDS\>  
  \&access\_token=\<ACCESS\_TOKEN\>  
Query String Parameters  
Parameter	Value  
access\_token  
Required  
String

The app user's User access token.

fields  
Comma-separated list

A comma-separated list of fields and edges you want returned. If omitted, default fields will be returned.

Fields  
Field Name	Description  
copyright\_check\_status

Used to determine if an uploaded video is violating copyright. Key-values pairs return include:

matches\_found set to one of the following:  
true – the video is violating copyright  
false – the video is not violating copyright  
status set to one of the following:  
completed – the detection process has finished  
error – an error occurred during the detection process  
in\_progress – the detection process is ongoing  
not\_started – the detection process has not started  
id

Instagram Container ID, represented in code examples as \<IG\_CONTAINER\_ID\>

status

Publishing status. If status\_code is ERROR, this value will be an error subcode.

status\_code

The container's publishing status. Possible values:

EXPIRED — The container was not published within 24 hours and has expired.  
ERROR — The container failed to complete the publishing process.  
FINISHED — The container and its media object are ready to be published.  
IN\_PROGRESS — The container is still in the publishing process.  
PUBLISHED — The container's media object has been published.  
Edges  
There are no edges on this node.

Response  
A JSON-formatted object containing default and requested fields.

{  
  "\<FIELD\>":"\<VALUE\>",  
  ...  
}  
Example Request  
curl \-X GET \\  
  'https://graph.instagram.com/17889615691921648?fields=status\_code\&access\_token=IGQVJ...'  
Sample Response  
{  
  "status\_code": "FINISHED",  
  "id": "17889615691921648"  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.  
"  
&  
"  
IG Hashtag Search  
This root edge allows you to get IG Hashtag IDs.

Available for the Instagram API with Facebook Login.

Creating  
This operation is not supported.

Reading  
Getting a Hashtag ID  
GET /ig\_hashtag\_search?user\_id=\<USER\_ID\>\&q=\<QUERY\_STRING\>

Returns the ID of an IG Hashtag. IDs are both static and global (i.e, the ID for \#bluebottle will always be 17843857450040591 for all apps and all app users).

Query String Parameters  
\<USER\_ID\> (required) — The ID of the IG User performing the request.  
\<QUERY\_STRING\> (required) — The hashtag name to query.  
Limitations  
You can query a maximum of 30 unique hashtags within a 7 day period.  
The API will return a generic error for any queries that include hashtags that we have deemed sensitive or offensive.  
Requirements

Type	Description  
Features

Instagram Public Content Access

Permissions

instagram\_basic

If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required: ads\_management, business\_management, or pages\_read\_engagement.

Tokens

A User access token of a Facebook User who has been approved for tasks on the connected Facebook Page.

Sample Request  
curl \-X GET \\  
 "https://graph.facebook.com/v24.0/ig\_hashtag\_search?user\_id=17841405309211844\&q=bluebottle\&access\_token={access-token}"  
Sample Response  
{  
    "data": \[  
        {  
            "id": "17843857450040591"  
        }  
    \]  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
Instagram (IG) Hashtag  
Represents an Instagram hashtag.

Limitations  
Only available for Facebook Login for Business  
Creating  
This operation is not supported.

Reading  
GET /\<IG\_HASHTAG\_ID\>

Returns Fields and Edges on an IG Hashtag.

Limitations  
You can query a maximum of 30 unique hashtags within a 7 day period.

Requirements  
Type	Description  
Features

Instagram Public Content Access

Permissions

instagram\_basic

If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required: ads\_management, business\_management, or pages\_read\_engagement.

Tokens

The app user's User access token.

Request Syntax  
GET https://graph.facebook.com/\<IG\_HASHTAG\_ID\>  
  ?fields={fields}  
  \&access\_token={access-token}  
Query String Parameters  
Include the following query string parameters to augment the request.

Key	Value  
access\_token  
Required  
String

The app user's Instagram User Access Token.

fields  
Comma-separated list

A comma-separated list of Fields and Edges you want returned. If omitted, default fields will be returned.

Fields  
You can use the fields query string parameter to request the following Fields on an IG Hashtag.

Field Name	Description  
id

The hashtag's ID (included by default). IDs are static and global.

name

The hashtag's name, without the leading hash symbol.

Edges  
You can request the following edges as path parameters or by using the fields query string parameter.

Edge	Description  
recent\_media

Get a list of the most recently published photo and video IG Media objects published with a specific hashtag.

top\_media

Returns the most popular photo and video IG Media objects that have been tagged with the hashtag.

Response  
A JSON-formatted object containing default and requested Fields.

{  
  "\<FIELD\_NAME\>":"\<FIELD\_VALUE",  
  ...  
}  
Sample Request  
GET https://graph.facebook.com/17841593698074073  
  ?fields=id,name  
  \&access\_token=EAADd...  
Sample Response  
{  
  "id": "17841593698074073",  
  "name": "coke"  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
IG Hashtag Recent Media  
Represents a collection of the most recently published photo and video IG Media objects that have been tagged with a hashtag.

Available for the Instagram API with Facebook Login.

Creating  
This operation is not supported.

Reading  
Returns a list of the most recently published photo and video IG Media objects published with a specific hashtag.

Requirements  
Type	Description  
Features

Instagram Public Content Access

Permissions

instagram\_basic

If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required: ads\_management, business\_management, or read\_pages\_engagement.

Tokens

A User access token of a Facebook User who has been approved for tasks on the connected Facebook Page.

Limitations  
Only returns public photos and videos.  
Only returns media objects published within 24 hours of query execution.  
Will not return promoted/boosted/ads media.  
Responses are paginated with a maximum limit of 50 results per page.  
Responses will not always be in chronological order.  
You can query a maximum of 30 unique hashtags within a 7 day period.  
You cannot request the username field on returned media objects.  
This endpoint only returns an after cursor for paginated results; a before cursor will not be included. In addition, the after cursor value will always be the same for each page, but it can still be used to get the next page of results in the result set.  
Syntax  
GET /\<IG\_HASHTAG\_ID\>/recent\_media?user\_id=\<USER\_ID\>\&fields=\<LIST\_OF\_FIELDS\>

Parameters  
Parameter	Description  
fields

A comma-separated list of fields on a media object

Value	Description  
caption

The caption for the media object

children

Media objects in a carousel Album IG Media, if applicable

comments\_count

The number of comments on the media object

id

The ID for the media object

like\_count

The number of likes for the media object. Will be omitted if the media owner has hidden like counts in it

media\_type

The type of media: CAROUSEL\_ALBUM, IMAGE, or VIDEO.

media\_url

The URL for the media object. Not returned for Album IG Media

permalink

The permalink for the media object

timestamp

Unix timestamp for when the media object was published

user\_id

The ID for person querying the data

Example Request  
GET graph.facebook.com/17873440459141021/recent\_media  
  ?user\_id=17841405309211844  
  \&fields=id,media\_type,comments\_count,like\_count  
Response  
An array of IG Media objects. Excess results will be paginated.

Sample Response  
{  
  "data": \[  
    {  
      "id": "17880997618081620",  
      "media\_type": "IMAGE",  
      "comments\_count": 84,  
      "like\_count": 177  
    },  
    {  
      "id": "17871527143187462"  
      "media\_type": "IMAGE",  
      "comments\_count": 24,  
      "like\_count": 57  
    },  
    {         
      "id": "17896450804038745"  
      "media\_type": "IMAGE",  
      "comments\_count": 19,  
      "like\_count": 36  
    }  
  \],  
  "paging":  
    {  
      "cursors":  
        {  
          "after": "NTAyYmE4..."  
        },  
      "next": "https://graph.facebook.com/..."  
    }  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
IG Hashtag Top Media  
Represents a collection of the most popular photo and video IG Media objects that have been tagged with a hashtag.

Popularity is determined by a mix of views and viewer interaction using the same methodology that determines the top posts when searching for a hashtag on www.instagram.com.

Available for the Instagram API with Facebook Login.

Creating  
This operation is not supported.

Reading  
Returns the most popular photo and video IG Media objects that have been tagged with the hashtag.

Requirements  
Type	Description  
Features

Instagram Public Content Access

Permissions

instagram\_basic

If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required: ads\_management, business\_management, or pages\_read\_engagement.

Tokens

A User access token of a Facebook User who has been approved for tasks on the connected Facebook Page.

Limitations  
This edge only returns public photos and videos.  
Will not return promoted/boosted/ads media.  
Responses are paginated with a maximum limit of 50 results per page.  
You can query a maximum of 30 unique hashtags within a 7 day period.  
You cannot request the username field on returned media objects.  
This endpoint only returns an after cursor for paginated results; a before cursor will not be included. In addition, the after cursor value will always be the same for each page, but it can still be used to get the next page of results in the result set.  
Syntax  
GET /\<IG\_HASHTAG\_ID\>/top\_media?user\_id=\<IG\_USER\_ID\>\&fields=\<LIST\_OF\_FIELDS\>

Query String Parameters  
\<IG\_USER\_ID\> (required) — The ID of the Instagram Business or Creator Account performing the query.  
\<LIST\_OF\_FIELDS\> — A comma-separated list of fields you want returned. See Returnable Fields.  
Response  
An array of IG Media objects. Excess results will be paginated.

Returnable Fields  
You can use the fields parameter to request the following fields on returned media objects:

caption  
children (only returned for Album IG Media)  
comments\_count  
id  
like\_count – field will be omitted if media owner has hidden like counts in it.)  
media\_type  
media\_url (not returned for Album IG Media)  
permalink  
timestamp  
Example Request  
GET graph.facebook.com/17873440459141021/top\_media  
  ?user\_id=17841405309211844  
  \&fields=id,media\_type,comments\_count,like\_count  
Sample Response  
{  
  "data": \[  
    {  
      "id": "17880997618081620",  
      "media\_type": "IMAGE",  
      "comments\_count": 84,  
      "like\_count": 177  
    },  
    {  
      "id": "17871527143187462"  
      "media\_type": "IMAGE",  
      "comments\_count": 24,  
      "like\_count": 57  
    },  
    {         
      "id": "17896450804038745"  
      "media\_type": "IMAGE",  
      "comments\_count": 19,  
      "like\_count": 36  
    },  
    ... // Results truncated for clarity  
  \],  
  "paging":  
    {  
      "cursors":  
        {  
          "after": "NTAyYmE4..."  
        },  
      "next": "https://graph.facebook.com/..."  
    }  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
IG Media  
Represents an Instagram album, photo, or video (uploaded video, live video, reel, or story).

If you are migrating from Marketing API Instagram Ads endpoints to Instagram Platform endpoints, be aware that some field names are different.

Introducing the following field:

legacy\_instagram\_media\_id  
The following Marketing API Instagram Ads endpoint fields are not supported:

filter\_name  
location  
location\_name  
latitude  
longitude  
Creating  
This operation is not supported.

Reading  
GET /\<IG\_MEDIA\_ID\>

Gets fields and edges on Instagram media.

Requirements  
Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_basic  
pages\_read\_engagement  
If the app user was granted a role via the Business Manager on the Page connected to your app user's Instagram professional account, your app will also need one of:

ads\_management  
ads\_read  
Limitations  
Fields that return aggregated values don't include ads-driven data. For example, comments\_count returns the number of comments on a photo, but not comments on ads that contain that photo.  
Captions don't include the @ symbol unless the app user is also able to perform admin-equivalent tasks on the app.  
Some fields, such as permalink, cannot be used on photos within albums (children).  
Live video Instagram Media can only be read while they are being broadcast.  
This API returns only data for media owned by Instagram professional accounts. It can not be used to get data for media owned by personal Instagram accounts.  
Request Syntax  
GET https://\<HOST\_URL\>/\<API\_VERSION\>/\<IG\_MEDIA\_ID\> \\  
  ?fields=\<LIST\_OF\_FIELDS\> \\  
  \&access\_token=\<ACCESS\_TOKEN\>  
Path Parameters  
Placeholder	Value  
\<API\_VERSION\>

The latest version is: v24.0	  
The API version your app is using. If not specified in your API calls this will be the latest version at the time you created your Meta app or, if that version is no longer available, the oldest version available.Learn more about versioning.

\<HOST\_URL\>

The host URL your app is using to query the endpoint.

\<IG\_MEDIA\_ID\>

Required. ID for the media to be published.

Query String Parameters  
Key	Placeholder	Value  
access\_token

\<ACCESS\_TOKEN\>

Required. The app user's Facebook or Instagram User access token.

fields

\<LIST\_OF\_FIELDS\>

Comma-separated list of fields you want returned.

Fields  
Public fields can be read via field expansion.

Field	Description  
alt\_text  
Public

Descriptive text for images, for accessibility.

boost\_ads\_list

Offers an overview of all Instagram ad information associated with the organic media for ads with ACTIVE status. It includes relative ad ID and ad delivery status. Available for Instagram API with Facebook Login only.

boost\_eligibility\_info

The field provides information about boosting eligibility of a Instagram instagram media as an ad and additional details if not eligible. Available for Instagram API with Facebook Login only.

caption  
Public

Caption. Excludes album children. The @ symbol is excluded, unless the app user can perform admin-equivalent tasks on the Facebook Page connected to the Instagram account used to create the caption. Available for Instagram API with Facebook Login only.

comments\_count  
Public

Count of comments on the media. Excludes comments on album child media and the media's caption. Includes replies on comments.

copyright\_check\_information.status

Returns status and matches\_found objects

status objects	Description  
status

completed – the detection process has finished  
error – an error occurred during the detection process  
in\_progress – the detection process is ongoing  
not\_started – the detection process has not started  
matches\_found

Set to one of the following:

false if the video does not violate copyright,  
true if the video does violate copyright  
If a video is violating copyright, the copyright\_matches is returned with an array of objects about the copyrighted material, when the violation is occurring in the video, and the actions take to mitigate the violation.

copyright\_matches objects	Description  
author

the author of the copyrighted video

content\_title

the name of the copyrighted video

matched\_segments

An array of objects with the following key-value pairs:

duration\_in\_seconds – the number of seconds the content violates copyright  
segment\_type – either AUDIO or VIDEO  
start\_time\_in\_seconds – set to the start time of the video  
owner\_copyright\_policy

Objects returned include:

name – The name for the copyright owners' policy  
actions – An array of action objects with the mitigations steps taken defined by the copyright owner's policy. May include different mitigations steps for different locations.  
action – The mitigation action taken against the video violating copyright. Different mitigation steps can be taken for different countries. Can be one of the following values:  
BLOCK – The video is blocked from the audiences listed in the geos array  
MUTE \- The video is muted for audiences listed in the geos array  
id  
Public

Media ID.

is\_comment\_enabled

Indicates if comments are enabled or disabled. Excludes album children.

is\_shared\_to\_feed  
Public

For Reels only. When true, indicates that the reel can appear in both the Feed and Reels tabs. When false, indicates that the reel can only appear in the Reels tab.

Neither value determines whether the reel actually appears in the Reels tab because the reel may not meet eligibilty requirements or may not be selected by our algorithm. See reel specifications for eligibility critera.

legacy\_instagram\_media\_id

The ID for Instagram media that was created for Marketing API endpoints for v21.0 and older.

like\_count

Count of likes on the media, including replies on comments. Excludes likes on album child media and likes on promoted posts created from the media.

If queried indirectly through another endpoint or field expansion the like\_count field is omitted if the media owner has hidden like counts.

media\_product\_type  
Public

Surface where the media is published. Can be AD, FEED, STORY or REELS. Available for Instagram API with Facebook Login only.

media\_type  
Public

Media type. Can be CAROUSEL\_ALBUM, IMAGE, or VIDEO.

media\_url  
Public

The URL for the media.

The media\_url field is omitted from responses if the media contains copyrighted material or has been flagged for a copyright violation. Examples of copyrighted material can include audio on reels.

owner  
Public

Instagram user ID who created the media. Only returned if the app user making the query also created the media; otherwise, username field is returned instead.

permalink  
Public

Permanent URL to the media.

shortcode  
Public

Shortcode to the media.

thumbnail\_url  
Public

Media thumbnail URL. Only available on VIDEO media.

timestamp  
Public

ISO 8601-formatted creation date in UTC (default is UTC ±00:00).

username  
Public

Username of user who created the media.

view\_count  
Public

View count for Instagram reels, which includes both paid and organic metrics.

Available for Business Discovery API only.

Edges  
Public edges can be returned through field expansion.

Edge	Description  
children  
Public.

Represents a collection of Instagram Media objects on an album Instagram Media.

collaborators

Represents a list of users who are added as collaborators on an Instagram Media object. Available for Instagram API with Facebook Login only.

comments

Represents a collection of Instagram Comments on an Instagram Media object.

insights

Represents social interaction metrics on an Instagram Media object.

cURL Example  
Example request  
curl \-X GET \\  
  'https://graph.instagram.com/v24.0/17895695668004550?fields=id,media\_type,media\_url,owner,timestamp\&access\_token=IGQVJ...'  
Example response  
{  
  "id": "17918920912340654",  
  "media\_type": "IMAGE",  
  "media\_url": "https://sconten...",  
  "owner": {  
    "id": "17841405309211844"  
  },  
  "timestamp": "2019-09-26T22:36:43+0000"  
}  
Updating  
POST /\<IG\_MEDIA\_ID\>

Enable or disable comments on an Instagram Media.

Requirements  
Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_manage\_comments  
instagram\_basic  
instagram\_manage\_comments  
pages\_read\_engagement  
If the app user was granted a role via the Business Manager on the Page connected to the targeted IG User, you will also need one of:

ads\_management  
ads\_read  
Limitations  
Live video Instagram Media not supported.

Request Syntax  
POST https://\<HOST\_URL\>/\<API\_VERSION\>/\<IG\_MEDIA\_ID\>  
  ?comment\_enabled=\<BOOL\>  
  \&access\_token=\<ACCESS\_TOKEN\>  
Path Parameters  
Placeholder	Value  
\<API\_VERSION\>

The latest version is: v24.0	  
The API version your app is using. If not specified in your API calls this will be the latest version at the time you created your Meta app or, if that version is no longer available, the oldest version available.Learn more about versioning.

\<HOST\_URL\>

The host URL your app is using to query the endpoint.

\<IG\_MEDIA\_ID\>

Required. ID for the media to be published.

Query String Parameters  
Key	Placeholder	Value  
access\_token

\<ACCESS\_TOKEN\>

Required. App user's user access token.

comment\_enabled

\<BOOL\>

Required. Set to true to enable comments or false to disable comments.

cURL Example  
Example request  
curl \-i \-X POST \\  
 "https://graph.instagram.com/v24.0/17918920912340654?comment\_enabled=true\&access\_token=EAAOc..."  
Example response  
{  
  "success": true  
}  
Deleting  
This operation is not supported.

"  
&  
"  
Children  
Represents a collection of IG Media objects on an album IG Media.

Requirements  
Instagram AP with Instagram Loging	Instagram API with Facebook Login  
Access Tokens

Instagram User user access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_basic  
pages\_read\_engagement  
If the app user was granted a role via the Business Manager on the Page connected to the targeted IG User, you will also need one of:

ads\_management  
ads\_read  
Creating  
This operation is not supported.

Reading  
Getting Child Media Objects  
GET /\<IG\_MEDIA\_ID\>/children

Returns a list of IG Media objects on an album IG Media object.

Limitations  
Some fields, such as permalink, cannot be used on photos within albums (children).  
Sample Request  
GET graph.facebook.com  
  /17896450804038745/children  
Sample Response  
{  
  "data": \[  
    {  
      "id": "17880997618081620"  
    },  
    {  
      "id": "17871527143187462"  
    }  
  \]  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.  
"  
&  
"  
Collaborators  
Represents a list of users who are added as collaborators on an IG Media object.

Available for the Instagram API with Facebook Login.

Creating  
This operation is not supported.

Reading  
Get a list of Instagram users as collaborators and their invitation status on an IG Media object.

GET /\<IG\_MEDIA\_ID\>

Limitations  
Up to 5 Instagram accounts can be added as collaborators  
Only IG users who have enabled collaborator tagging will be returned in the response  
Collaborators tagging supports Feed image, Reels and Carousel, Stories is not supported  
Requirements  
Type	Description  
Access Tokens

User – User must have created the IG Media object

Permissions

instagram\_basic  
pages\_read\_engagement

If the app user was granted a role on the Page via the Business Manager, you also need one of the following:

ads\_management  
ads\_read

Request syntax  
GET https://graph.facebook.com/\<API\_VERSION\>/\<IG\_MEDIA\_ID\>/collaborators&\<USER\_ACCESS\_TOKEN\>  
Sample Response  
{  
  "data": \[  
    {  
      "id": "90010775360791",  
      "username": "realtest1",  
      "invite\_status": "Accpeted"  
    },  
    {  
      "id": "17841449208283139",  
      "username": "realtest2",  
      "invite\_status": "Pending"  
    }  
  \]  
}  
Path Parameters  
Placeholder	Value  
\<API\_VERSION\>

API version.

\<IG\_MEDIA\_ID\>

Required. The ID for your app user's Instagram media.

Query String Parameters  
Key	Placeholder	Value  
access\_token

\<USER\_ACCESS\_TOKEN\>

Required. Your app user's User access token.

Response fields  
Field Name	Description  
id

The App-scoped ID for the Instagram account of the potential collaborator

invite\_status

The status for the invitation sent to a potential collaborator. Can be one of the following:

Accepted  
Pending  
username

Instagram profile username for the potential collaborator

Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
Comments  
Represents a collection of IG Comments on an IG Media object.

Non-Organic Comments  
Comments on Ads containing IG Media (i.e. non-organic comments) are of a different type and are not supported. To get non-organic comments, use the Marketing API and request the Ad's effective\_instagram\_media\_id. You can then query the returned ID's /comments edge to get a collection of non-organic Instagram Comments. Refer to the Marketing API's Post Moderation guide for more information.

Requirements  
Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_manage\_comments  
instagram\_basic  
instagram\_manage\_comments  
pages\_read\_engagement  
If the app user was granted a role via the Business Manager on the Page connected to the targeted IG User, you will also need one of:

ads\_management  
ads\_read  
Creating  
Creating a Comment on a Media Object  
POST /\<IG\_MEDIA\_ID\>/comments?message=\<MESSAGE\_CONTENT\>

Creates an IG Comment on an IG Media object.

Limitations  
Comments on live video IG Media are not supported.

Query String Parameters  
Query string parameters are optional unless indicated as required.

\<MESSAGE\_CONTENT\> (required) — The text to be included in the comment.  
Example Request  
POST graph.facebook.com  
  /17895695668004550/comments?message=This%20is%20awesome\!  
Example Response  
{  
  "id": "17870913679156914"  
}  
Reading  
Getting Comments on a Media Object  
GET /\<IG\_MEDIA\_ID\>/comments

Returns a list of IG Comments on an IG Media object.

Limitations  
Requests made using API version 3.1 or older will have results returned in chronological order. Requests made using version 3.2+ will have results returned in reverse chronological order.  
Returns only top-level comments. Replies to comments are not included unless you use field expansion to request the replies field.  
Returns a maximum of 50 comments per query.  
Comments cannot be filtered by timestamp.  
Permissions  
An access token from a User who created the IG Media object, with the following permissions:

instagram\_basic  
instagram\_manage\_comments  
If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required:

ads\_management  
ads\_read  
Sample Request  
GET graph.facebook.com  
  /17895695668004550/comments  
Sample Response  
{  
  "data": \[  
    {  
      "timestamp": "2017-08-31T19:16:02+0000",  
      "text": "This is awesome\!",  
      "id": "17870913679156914"  
    },  
    {  
      "timestamp": "2017-08-31T18:10:30+0000",  
      "text": "\*Sniff\*",  
      "id": "17873440459141021"  
    }  
  \]  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
Instagram Media Insights  
Represents social interaction metrics on your app user's Instagram Media object.

Instagram Insights are now available for Instagram API with Instagram Login. Learn more.

Introducing the views metric for FEED, STORY, and REELS media product types.

The following metrics have been deprecated for v22.0 and will be deprecated for all versions on April 21, 2025:

plays  
clips\_replays\_count  
ig\_reels\_aggregated\_all\_plays\_count  
impressions  
Note: API requests with the impressions metric will continue to return data for media created on or before July 1, 2024 for v21.0 and older. API requests made after April 21, 2025 for media created on or after July 2, 2024 will return an error.

The video\_views metric has been deprecated.

Visit the Instagram Platform Changelog for more information.

Creating  
This operation is not supported.

Reading  
GET /\<INSTAGRAM\_MEDIA\_ID\>/insights

Get insights data on an Instagram Media object.

Limitations  
If insights data you are requesting does not exist or is currently unavailable, the API returns an empty data set instead of 0 for individual metrics.  
Data used to calculate metrics can be delayed up to 48 hours.  
Metrics data is stored for up to 2 years.  
The API only reports organic interaction metrics; interactions on ads containing a media object are not counted.  
Album metrics  
Insights data is not available for any media within an Instagram Media album.  
Story media metrics  
Story media metrics are only available for 24 hours.  
Set up Instagram webhooks and subscribe to the story\_insights field to get story insights for a story before they expire. You may receive data after the story expires if the story is added to a highlight. This may return different results for API calls, webhook notifications, and UIs.  
Story media metrics with values less than 5 return an error code 10 with the message (\#10) Not enough viewers for the media to show insights.  
For Stories created by users in Europe and Japan, the replies metric now returns a value of 0\.  
Replies made by users in Europe and Japan are not included in replies calculations for story media metrics.  
Webhooks  
Insights webhook for Instagram API with Instagram Login is not supported.  
Requirements  
Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_manage\_insights  
instagram\_basic  
instagram\_manage\_insights  
pages\_read\_engagement  
If the app user was granted a role on the Page connected to your app user's Instagram professional account via the Business Manager, your app will also need:

ads\_management  
ads\_read  
Request syntax  
GET "https://\<HOST\_URL\>/\<API\_VERSION\>/\<INSTAGRAM\_MEDIA\_ID\>/insights  
  ?metric=\<LIST\_OF\_METRICS\>  
  \&period=\<LIST\_OF\_TIME\_PERIODS\>  
  \&breakdown=\<LIST\_OF\_BREAKDOWNS\>  
  \&access\_token=\<ACCESS\_TOKEN\>"  
Path parameters

Placeholder	Value  
\<API\_VERSION\>

The latest version is: v24.0	  
The API version your app is using. If not specified in your API calls this will be the latest version at the time you created your Meta app or, if that version is no longer available, the oldest version available. Learn more about versioning.

\<HOST\_URL\>

The host URL your app is using to query the endpoint.

\<INSTAGRAM\_MEDIA\_ID\>

Required. The Instagram Media ID.

Query string parameters

Key	Placeholder	Value  
access\_token

\<ACCESS\_TOKEN\>

Required. The app user's Facebook or Instagram User access token.

breakdown

\<LIST\_OF\_BREAKDOWNS\>

Designates how to break down results into subsets.

metric

\<LIST\_OF\_METRICS\>

Required. Comma-separated list of metrics you want returned.

period

\<LIST\_OF\_TIME\_PERIODS\>

Comma-separated list of time periods you want returned. Values can be:

day  
week  
days\_28  
month  
lifetime  
total\_over\_range  
Metrics  
The following table shows the metrics and the media object types the are available on.

Metric	Media Product Type  
clips\_replays\_count

Deprecated for v22.0 and for all versions on April 21, 2025\.

The number of times your reel starts to play again after an initial play of your video. This is defined as replays of 1ms or more in the same reel session.

REELS

comments

Number of comments on the media object.

FEED (posts)  
REELS

follows

The number of Instagram users following your app user's Instagram professional account.

FEED (posts)  
STORY

ig\_reels\_aggregated\_all\_plays\_count

Deprecated for v22.0 and for all versions on April 21, 2025\.

The number of times your reel starts to play or replay after an impression is already counted. This is defined as plays of 1ms or more. Replays are counted after the initial play in the same reel session.

Note that this count may be greater than the sum of clips\_replays\_count and plays. This is because clips\_replays\_count and plays only count plays in the Instagram app, while ig\_reels\_aggregated\_all\_plays\_count also includes plays in the Facebook app through Cross App Recommendation (XAR).

REELS

ig\_reels\_avg\_watch\_time

The average amount of time spent playing the reel.

REELS

ig\_reels\_video\_view\_total\_time

The total amount of time the reel was played, including any time spent replaying the reel. Metric in development.

REELS

impressions

For media created after July 2, 2024, this metric is deprecated for v22.0+ and will be deprecated for all versions on April 21, 2025\. For media created before July 2, 2024, this metric will still be available.

Total number of times your app user's Instagram Media object has been seen.

FEED (posts)  
STORY

likes

Number of likes on the media object.

FEED (posts)  
REELS

navigation

This is the total number of actions taken from your story. These are made up of metrics like exited, forward, back and next story.

Available breakdown: story\_navigation\_action\_type

STORY

plays

Deprecated for v22.0 and for all versions on April 21, 2025\.

Number of times the reels starts to play after an impression is already counted. This is defined as video sessions with 1 ms or more of playback and excludes replays.

REELS

profile\_activity

The number of actions people take when they visit your profile after engaging with your post.

Available breakdown: action\_type (Available for media created after October 26, 2017.)

FEED (posts)  
STORY

profile\_visits

The number of times your profile was visited.

FEED (posts)  
STORY

reach

Number of unique Instagram users that have seen the reel at least once. Reach is different from impressions, which can include multiple views of a reel by the same account. Metric is estimated.

FEED (posts)  
REELS STORY

replies

Total number of replies (IG Comments) on the story IG Media object. Value does not include replies made by users in some regions. These regions include: Europe starting December 1, 2020 and Japan starting April 14, 2021\. If the Story was created by a user in one of these regions, returns a value of 0\.

STORY

saved

Number of time your app user's Instagram media was saved by an Instagram user.

FEED (posts)  
REELS

shares

Number of shares of the reel.

FEED (posts)  
REELS STORY

total\_interactions

Number of likes, saves, comments, and shares on the reel, minus the number of unlikes, unsaves, and deleted comments. Metric in development.

FEED (posts)  
REELS STORY

views

Total number of times the video IG Media has been seen.

Metric in development.

FEED (posts)  
REELS STORY

Breakdowns  
You can also include the breakdown parameter for specific metrics to divide data into smaller sets based on the specified breakdown value. Values can be:

breakdown value	Response values  
action\_type

Only compatible with the profile\_activity metric.

Break down results by the profile component within the native app that viewers tapped or clicked after viewing the app user's profile.

BIO\_LINK\_CLICKED  
CALL  
DIRECTION  
EMAIL  
OTHER  
TEXT  
story\_navigation\_action\_type

Only compatible with the navigation metric.

Break down results by navigation action taken by the viewer upon viewing the media within the native app. Adding all of these action types will give you the total navigation insights.

SWIPE\_FORWARD equals "Next Story"  
TAP\_BACK equals "Back"  
TAP\_EXIT equals "Exit"  
TAP\_FORWARD equals "Forward"  
NOTE: If you request a metric that doesn't support breakdowns, the API will return an error ("An unknown error has occurred."), so be careful if requesting multiple metrics in a single query.

Response syntax  
On success your app receives a JSON object containing the results of your query. Results can include the following data, based on your query specifications:

{  
  "data": \[  
    {  
      "name": "\<NAME\>",  
      "period": "\<PERIOD\>",  
      "values": \[  
        {  
          "value": \<VALUE\>  
        }  
      \],  
      "title": "\<TITLE\>",  
      "description": "\<DESCRIPTION\>",  
      "total\_value": {  
        "value":\<VALUE\>,  
        "breakdowns": \[  
          {  
            "dimension\_keys": \[  
              "\<DIMENSION\_KEY\_1\>",  
              "\<DIMENSION\_KEY\_2\>"  
              ...  
            \],  
            "results": \[  
              {  
                "dimension\_values": \[  
                  "\<DIMENSION\_VALUE\_1\>",  
                  "\<DIMENSION\_VALUE\_2\>"  
                  ...  
                \],  
                "value": \<VALUE\>  
              },  
              ...  
            \]  
          }  
        \]  
      },  
      "id": "\<ID\>"  
    }  
  \]  
}  
Response contents  
Property	Value Type	Description  
data

Array

An array containing an object describing your request results.

name

String

Metric name.

period

String

Period requested. Period is automatically set to lifetime in the request and cannot be changed, so this value will always be lifetime.

values

Array

An array containing an object describing requested metric values.

value

Integer

For data.values.value, sum of requested metric values.

For data.total\_value.value, sum of requested breakdown values.

For data.total\_value.breakdowns.results.value, sum of breakdown set values.

title

String

Metric title.

description

String

Metric description.

id

String

A string describing the query's path parameters.

total\_value

Object

Object describing requested breakdown values (if breakdowns were requested).

breakdowns

Array

An array of objects describing the breakdowns requested and their results.

dimension\_keys

Array

Array of strings describing breakdowns requested.

results

Array

An array of objects describing each breakdown set.

dimension\_values

String

An array of strings describing breakdown set values. Values can be mapped to dimension\_keys.

paging

Object

An object containing URLs used to request the next set of results. See Paginated Results for more information.

previous

String

URL to retrieve the previous page of results. See Paginated Results for more information.

next

String

URL to retrieve the next page of results. See Paginated Results for more information.

Examples  
Sample post metric request  
The following is a request from an app that uses Facebook Login.

curl \-i \-X GET \\  
 "https://graph.facebook.com/v24.0/17932174733377207/insights?metric=profile\_activity\&breakdown=action\_type\&access\_token=EAAOc..."  
Sample post metric response  
{  
  "data": \[  
    {  
      "name": "profile\_activity",  
      "period": "lifetime",  
      "values": \[  
        {  
          "value": 4  
        }  
      \],  
      "title": "Profile activity",  
      "description": "\[IG Insights\] This header is the name of a metric that appears on an educational info sheet for a particular post, story, video or promotion. This metric is the sum of all profile actions people take when they engage with this content.",  
      "total\_value": {  
        "value": 4,  
        "breakdowns": \[  
          {  
            "dimension\_keys": \[  
              "action\_type"  
            \],  
            "results": \[  
              {  
                "dimension\_values": \[  
                  "email"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "text"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "direction"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "bio\_link\_clicked"  
                \],  
                "value": 1  
              }  
            \]  
          }  
        \]  
      },  
      "id": "17932174733377207/insights/profile\_activity/lifetime"  
    }  
  \]  
}  
Sample story metric request  
The following is a request from an app that uses Instagram Login.

curl \-i \-X GET \\  
 "https://graph.instagram.com/v24.0/17969782069736348/insights?metric=navigation\&breakdown=story\_navigation\_action\_type\&access\_token=EAAOc..."  
Sample story metric response  
{  
  "data": \[  
    {  
      "name": "navigation",  
      "period": "lifetime",  
      "values": \[  
        {  
          "value": 25  
        }  
      \],  
      "title": "Navigation",  
      "description": "This is the total number of actions taken from your story. These are made up of metrics like exited, forward, back and next story.",  
      "total\_value": {  
        "value": 25,  
        "breakdowns": \[  
          {  
            "dimension\_keys": \[  
              "story\_navigation\_action\_type"  
            \],  
            "results": \[  
              {  
                "dimension\_values": \[  
                  "tap\_forward"  
                \],  
                "value": 19  
              },  
              {  
                "dimension\_values": \[  
                  "tap\_back"  
                \],  
                "value": 4  
              },  
              {  
                "dimension\_values": \[  
                  "tap\_exit"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "swipe\_forward"  
                \],  
                "value": 1  
              }  
            \]  
          }  
        \]  
      },  
      "id": "17969782069736348/insights/navigation/lifetime"  
    }  
  \]  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
IG Media Product Tags  
Represents product tags on an IG Media. See Product Tagging guide for complete usage details.

Available for the Instagram API with Facebook Login.

Creating  
POST /\<IG\_MEDIA\_ID\>/product\_tags

Create or update product tags on an existing IG Media.

Limitations  
Instagram Creator accounts are not supported.  
Stories, Instagram TV, Live, and Mentions are not supported.  
Tagging media is additive until the 5 tag limit has been reached. If the targeted media has already been tagged by a product in the request, the old tag's x and y values will be updated with their new values (a new tag will not be added).  
Requirements  
Type	Requirement  
Access Tokens

User

Business Roles

The app user must have an admin role on the Business Manager that owns the IG User's Instagram Shop.

Instagram Shop

The IG User that owns the IG Media must have an approved Instagram Shop with a product catalog containing products.

Permissions

ad\_reads  
\[catalog\_management  
instagram\_basic  
instagram\_shopping\_tag\_products  
If the app user was granted a role via the Business Manager on the Page connected to the targeted IG User, you will also need:

ads\_management

Request Syntax  
POST https://graph.facebook.com/\<API\_VERSION\>/\<IG\_MEDIA\_ID\>/product\_tags  
  ?updated\_tags=\<LIST\_OF\_UPDATED\_TAGS\>  
  \&access\_token=\<ACCESS\_TOKEN\>  
Path Parameters  
Placeholder	Value  
\<API\_VERSION\>

API version

\<IG\_MEDIA\_ID\>

Required. IG Media ID.

Query String Parameters  
Key	Placeholder	Value  
access\_token

\<ACCESS\_TOKEN\>

Required. App user's User access token.

updated\_tags

\<LIST\_OF\_UPDATED\_TAGS\>

Required. Applies only to images and videos. An array of objects specifying which product tags to tag the image or video with (maximum of 5; tags and product IDs must be unique). Each object should have the following information:

product\_id — Required. Product ID.  
x — Images only. An optional float that indicates percentage distance from left edge of the published media image. Value must be within 0.0–1.0 range.  
y — Images only. An optional float that indicates percentage distance from top edge of the published media image. Value must be within 0.0–1.0 range.  
For example:

\[{product\_id:'3231775643511089',x:0.5,y:0.8}\]

Response  
An object indicating success or failure.

{  
  "success": {success}  
}  
Response Contents  
Property	Value  
success

Returns true if able to update the IG Media's product tags, otherwise returns false.

cURL Example  
Request  
curl \-i \-X POST \\  
 "https://graph.facebook.com/v24.0/90010778325754/product\_tags?updated\_tags=%5B%0A%20%20%7B%0A%20%20%20%20product\_id%3A'3859448974125379'%2C%0A%20%20%20%20x%3A%200.5%2C%0A%20%20%20%20y%3A%200.8%0A%20%20%7D%0A%5D\&access\_token=EAAOc..."  
For reference, here is the HTML-decoded POST payload string:

https://graph.facebook.com/v24.0/90010778325754/product\_tags?updated\_tags=\[  
  {  
    product\_id:'3859448974125379',  
    x: 0.5,  
    y: 0.8  
  }  
\]\&access\_token=EAAOc...  
Response  
{  
  "success": true  
}  
Reading  
GET /\<IG\_MEDIA\_ID\>/product\_tags

Get a collection of product tags on an IG Media. See the Product Tagging guide for complete product tagging steps.

Limitations  
Instagram Creator accounts are not supported.  
Stories, Instagram TV, Reels, Live, and Mentions are not supported.  
Requirements  
Type	Requirement  
Access Tokens

User

Business Roles

The app user must have an admin role on the Business Manager that owns the IG User's Instagram Shop.

Instagram Shop

The IG User that owns the IG Media must have an approved Instagram Shop with a product catalog containing products.

Permissions

ad\_reads  
catalog\_management  
instagram\_basic  
instagram\_shopping\_tag\_products  
If the app user was granted a role via the Business Manager on the Page connected to the targeted IG User, you will also need:

ads\_management  
Request Syntax  
GET https://graph.facebook.com/\<API\_VERSION\>/\<IG\_MEDIA\_ID\>/product\_tags  
  ?access\_token=\<ACCESS\_TOKEN\>  
Path Parameters  
Placeholder	Value  
\<API\_VERSION\>

API version

\<IG\_MEDIA\_ID\>

Required. IG Media ID.

Query String Parameters  
Key	Placeholder	Value  
access\_token

\<ACCESS\_TOKEN\>

Required. App user's User access token.

Response  
A JSON-formatted object containing an array of product tags on an IG Media. Responses can include the following product tag fields:

{  
  "data": \[  
    {  
      "product\_id": {product-id},  
      "merchant\_id": {merchant-id},  
      "name": "{name}",  
      "price\_string": "{price-string}",  
      "image\_url": "{image-url}",  
      "review\_status": "{review-status}",  
      "is\_checkout": {is-checkout},  
      "stripped\_price\_string": "{stripped-price-string}",  
      "string\_sale\_price\_string": "{string-sale-price-string}",  
      "x": {x},  
      "y": {y}  
    }  
  \]  
}  
Response Contents  
Property	Value  
product\_id

Product ID.

merchant\_id

Merchant ID.

name

Product name.

price\_string

Price string.

image\_url

Product image URL.

review\_status

Product review status. Values can be:

approved — Product is approved.  
rejected — Product was rejected  
pending — Still undergoing review.  
outdated — Product was approved but has been edited and requires reapproval.  
"" — No review status.  
is\_checkout

If true, product can be purchased directly through the Instagram app. If false, product can only be purchased on the merchant's website.

stripped\_price\_string

Product short price string (price displayed in constrained spaces, such as $100 instead of 100 USD).

string\_sale\_price\_string

Product sale price.

x

A float that indicates percentage distance from left edge of media image. Value within 0.0–1.0 range.

y

A float that indicates percentage distance from top edge of media image. Value within 0.0–1.0 range.

cURL Example  
Request  
curl \-i \-X GET \\  
 "https://graph.facebook.com/v24.0/90010778325754/product\_tags?access\_token=EAAOc..."  
Response  
{  
  "data": \[  
    {  
      "product\_id": 3231775643511089,  
      "merchant\_id": 90010177253934,  
      "name": "Gummy Bears",  
      "price\_string": "$3.50",  
      "image\_url": "https://scont...",  
      "review\_status": "approved",  
      "is\_checkout": true,  
      "stripped\_price\_string": "$3.50",  
      "stripped\_sale\_price\_string": "$3",  
      "x": 0.5,  
      "y": 0.80000001192093  
    }  
  \]  
}  
"  
&  
"  
IG User  
Represents an Instagram Business Account or an Instagram Creator Account.

Throughout our documentation we use "Instagram User" and "Instagram Account" interchangeably. Both represent your app user's Instagram professional account.

Requirements  
Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User user access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_basic  
pages\_read\_engagement  
If the app user was granted a role via the Business Manager on the Page connected to the targeted IG User, you will also need one of:

ads\_management  
ads\_read  
If you are requesting the shopping\_product\_tag\_eligibility field for product tagging, you will also need:

catalog\_management  
instagram\_shopping\_tag\_products  
Business Roles

Not applicable.

If you are requesting the shopping\_product\_tag\_eligibility field for product tagging, the app user must have an admin role on the Business Manager that owns the IG User's Instagram Shop.

Instagram Shop

Not applicable.

If you are requesting the shopping\_product\_tag\_eligibility field for product tagging, the IG User must have an approved Instagram Shop with a product catalog containing products.

Creating  
This operation is not supported.

Reading  
GET /\<IG\_USER\_ID\>

Get fields and edges on an Instagram Business or Creator Account.

If you are migrating from Marketing API Instagram Ads endpoints to Instagram Platform endpoints, be aware that some field names are different.

Request Syntax  
GET https://graph.facebook.com/\<API\_VERSION\>/\<IG\_USER\_ID\>  
  ?fields=\<LIST\_OF\_FIELDS\>  
  \&access\_token=\<ACCESS\_TOKEN\>  
Path Parameters  
Placeholder	Value  
\<API\_VERSION\>

API version.

\<IG\_USER\_ID\>

Required. IG User ID.

Query String Parameters  
Key	Placeholder	Value  
access\_token

\<ACCESS\_TOKEN\>

Required. App user's User access token.

fields

\<LIST\_OF\_FIELDS\>

Comma-separated list of IG User fields you want returned for each IG User in the result set.

Fields  
Public fields can be returned by an edge using field expansion. Only a few fields will be available for accessing Page-backed Instagram accounts.

Field Name	Description  
alt\_text  
Public

Descriptive text for images, for accessibility.

biography  
Public

Profile bio text.

followers\_count  
Public

Total number of Instagram users following the user.

follows\_count

Total number of Instagram users the user follows.

has\_profile\_pic

Indicates whether your app user's Instagram professional account has a profile picture.

id  
Public

App-scoped User ID. Available for Page-backed Instagram accounts.

is\_published

Indicates whether your app user's Instagram account is published. Available for Page-backed Instagram accounts.

legacy\_instagram\_user\_id

Your app user's Instagram ID that was created for Marketing API endpoints for v21.0 and older. Available for Page-backed Instagram accounts.

media\_count  
Public

Total number of IG Media published on your app user's account.

name

Your app user's Instagram profile name.

profile\_picture\_url

Your app user's Instagram profile picture URL.

shopping\_product\_tag\_eligibility

Returns true if your app user has set up an Instagram Shop and is therefore eligible for product tagging, otherwise returns false.

username  
Public

Your app user's Instagram profile username.

website  
Public

Your app user's website URL.

Edges  
Edge	Description  
agencies

A list of businesses that can advertise for this Instagram professional account.

authorized\_adaccounts

Ad accounts that can advertise for this Instagram professional account.

business\_discovery

Get data about other Instagram Business or Instagram Creator IG Users.

connected\_threads\_user

Represents a Threads account connected to an Instagram account.

content\_publishing\_limit

Represents an IG User's current content publishing usage.

insights

Represents social interaction metrics on an IG User.

instagram\_backed\_threads\_user

Represents a Threads account backed by an Instagram account.

live\_media

Represents a collection of live video IG Media on an IG User.

media

Represents a collection of IG Media on an IG User.

media\_publish

Publish an IG Container on an Instagram Business IG User.

mentions

Create an IG Comment on an IG Comment or captioned IG Media that an IG User has been @mentioned in by another Instagram user.

mentioned\_comment

Get data on an IG Comment in which an IG User has been @mentioned by another Instagram user.

mentioned\_media

Get data on an IG Media in which an IG User has been @mentioned in a caption by another Instagram user.

recently\_searched\_hashtags

Get IG Hashtags that an IG User has searched for within the last 7 days.

stories

Represents a collection of story IG Media objects on an IG User.

tags

Represents a collection of IG Media in which an IG User has been tagged by another Instagram user.

upcoming\_events

A list of events this Instagram professional account is hosting.

Response  
A JSON-formatted object containing default and requested fields and edges.

{  
  "\<FIELD\>":"\<VALUE\>",  
  ...  
}  
cURL Example  
Request  
curl \-X GET \\  
  'https://graph.facebook.com/v24.0/17841405822304914?fields=biography%2Cid%2Cusername%2Cwebsite\&access\_token=EAACwX...'  
Response  
{  
  "biography": "Dino data crunching app",  
  "id": "17841405822304914",  
  "username": "metricsaurus",  
  "website": "http://www.metricsaurus.com/"  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
IG User Available Catalogs  
Represents a collection of product catalogs in an IG User's Instagram Shop. See Product Tagging guide for complete usage details.

Available for the Instagram API with Facebook Login.

Creating  
This operation is not supported.

Reading  
GET /\<IG\_USER\_ID\>/available\_catalogs

Get the product catalog in an IG User's Instagram Shop.

Limitations  
Instagram Creator accounts are not supported.  
Stories, Instagram TV, Reels, Live, and Mentions are not supported.  
Only returns data on a single catalog because Instagram Shops are limited to a single catalog.  
Collaborative catalogs (shopping partner or affiliate creator catalogs) are not supported.  
Requirements  
Type	Requirement  
Access Tokens

User

Business Roles

The app user must have an admin role on the Business Manager that owns the IG User's Instagram Shop.

Instagram Shop

The IG User must have an approved Instagram Shop with a product catalog containing products.

Permissions

catalog\_management instagram\_basic instagram\_shopping\_tag\_products

If the app user was granted a role via the Business Manager on the Facebook Page connected to the targeted IG User, you will also need one of:

ads\_management ads\_read

Request Syntax  
GET https://graph.facebook.com/\<API\_VERSION\>/\<IG\_USER\_ID\>/available\_catalogs  
  ?fields=\<LIST\_OF\_FIELDS\>  
  \&access\_token=\<ACCESS\_TOKEN\>  
Path Parameters  
Placeholder	Value  
\<API\_VERSION\>

API version

\<IG\_USER\_ID\>

Required. App user's app-scoped user ID.

Query String Parameters  
Key	Placeholder	Value  
access\_token

\<ACCESS\_TOKEN\>

Required. App user's User access token.

fields

\<LIST\_OF\_FIELDS\>

Comma-separated list of catalog fields you want returned for each catalog in the result set.

Response  
A JSON-formatted object containing the data you requested.

{  
  "data": \[  
    {  
      "catalog\_id": "{catalog-id}",  
      "catalog\_name": "{catalog-name}",  
      "shop\_name": "{shop-name}",  
      "product\_count": {product-count}  
    }  
  \]  
}  
Response Contents  
Property	Value  
catalog\_id

Catalog ID.

catalog\_name

Catalog name.

shop\_name

Shop name.

product\_count

Number of products in catalog. Includes all products regardless of review status.

cURL Example  
Request  
curl \-i \-X GET \\  
 "https://graph.facebook.com/v24.0/90010177253934/available\_catalogs?access\_token=EAAOc..."  
Response  
{  
  "data": \[  
    {  
      "catalog\_id": "960179311066902",  
      "catalog\_name": "Jay's Favorite Snacks",  
      "shop\_name": "Jay's Bespoke",  
      "product\_count": 11  
    }  
  \]  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
IG User Business Discovery  
Allows you to get data about other Instagram Business or Creator IG Users.

Available for the Instagram API with Facebook Login.

Creating  
This operation is not supported.

Reading  
GET /\<IG\_USER\_ID\>?fields=business\_discovery.username(\<USERNAME\>)

Returns data about another Instagram Business or Creator IG User. Perform this request on the Instagram Business or Creator IG User who is making the query, and identify the targeted business with the username parameter.

Limitations  
Data about age-gated Instagram Business IG Users will not be returned.

Query String Parameters  
\<USERNAME\> (required) — The username of the Instagram Business or Creator IG User you want to get data about.  
Permissions  
A Facebook User access token with the following permissions:

instagram\_basic  
instagram\_manage\_insights  
pages\_read\_engagement  
If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required:

ads\_management  
ads\_read  
Field Expansion  
You can use field expansion get public fields on the targeted IG User. Refer to the IG User reference for a list of public fields.

Sample Request with Field Expansion  
Getting data about the Instagram Business IG User "Blue Bottle Coffee" and using field expansion to request its followers and media counts.

GET graph.facebook.com  
  /17841405309211844  
    ?fields=business\_discovery.username(bluebottle){followers\_count,media\_count}  
Sample Response  
{  
  "business\_discovery": {  
    "followers\_count": 267788,  
    "media\_count": 1205,  
    "id": "17841401441775531"  
  },  
  "id": "17841405309211844"  
}  
Accessing Edges with Field Expansion  
You can also use field expansion to access the /media edge on the targeted IG User and specify the fields and metrics that should be returned for each IG Media object. Refer to the Media node reference for a list of public fields.

Sample Request with Edge  
GET graph.facebook.com  
  /17841405309211844  
    ?fields=business\_discovery.username(bluebottle){followers\_count,media\_count,media}  
Sample Response with Edge  
{  
  "business\_discovery": {  
    "followers\_count": 267788,  
    "media\_count": 1205,  
    "media": {  
      "data": \[  
        {  
          "id": "17858843269216389"  
        },  
        {  
          "id": "17894036119131554"  
        },  
        {  
          "id": "17894449363137701"  
        },  
        {  
          "id": "17844278716241265"  
        },  
        {  
          "id": "17911489846004508"  
        }  
      \],  
    },  
    "id": "17841401441775531"  
  },  
  "id": "17841405309211844"  
}  
Pagination  
The /media edge supports cursor-based pagination, so when accessing it via field expansion, the response will include before and after cursors if the response contains multiple pages of data. Unlike standard cursor-based pagination, however, the response will not include previous or next fields, so you will have to use the before and after cursors to construct previous and next query strings manually in order to page through the returned data set.

Sample Request  
GET graph.facebook.com  
  /17841405309211844  
    ?fields=business\_discovery.username(bluebottle){media{comments\_count,like\_count,view\_count}}  
Sample Response  
{  
  "business\_discovery": {  
    "media": {  
      "data": \[  
        {  
          "comments\_count": 50,  
          "like\_count": 5837,  
          "view\_count": 7757,  
          "id": "17858843269216389"  
        },  
        {  
          "comments\_count": 11,  
          "like\_count": 2997,  
          "id": "17894036119131554"  
        },  
        {  
          "comments\_count": 28,  
          "like\_count": 3643,  
          "id": "17894449363137701"  
        },  
        {  
          "comments\_count": 43,  
          "like\_count": 4943,  
          "id": "17844278716241265"  
        },  
     \],  
   },  
   "id": "17841401441775531"  
  },  
  "id": "17841405976406927"  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
IG User Catalog Product Search  
Represents products and product variants that match a given search string in an IG User's Instagram Shop product catalog. See Product Tagging guide for complete usage details.

Available for Instagram Graph API only.

Creating  
This operation is not supported.

Reading  
GET /\<IG\_USER\_ID\>/catalog\_product\_search

Get a collection of products that match a given search string within the targeted IG User's Instagram Shop catalog.

Limitations  
Instagram Creator accounts are not supported.  
Stories, Instagram TV, Reels, Live, and Mentions are not supported.  
Products with a review\_status of rejected will be returned, however, IG Media cannot be tagged with rejected products.  
Although the API will not return an error when publishing a post tagged with an unapproved product, the tag will not appear on the published post until the product has been approved. Therefore, we recommend that you only allow your app users to publish posts with tags whose products have a review\_status of approved. This field is returned for each product by default when you get an app user's eligible products.  
Requirements  
Type	Requirement  
Access Tokens

User

Business Roles

The app user must have an admin role on the Business Manager that owns the IG User's Instagram Shop.

Instagram Shop

The IG User must have an approved Instagram Shop with a product catalog containing products.

Permissions

catalog\_management  
instagram\_basic  
instagram\_shopping\_tag\_products

If the app user was granted a role via the Business Manager on the Page connected to the targeted IG User, you will also need one of:

ads\_management  
ads\_read

Request Syntax  
GET https://graph.facebook.com/\<API\_VERSION\>/\<IG\_USER\_ID\>/catalog\_product\_search  
  ?catalog\_id=\<CATALOG\_ID\>  
  \&q=\<QUERY\_STRING\>  
  \&access\_token=\<ACCESS\_TOKEN\>  
Path Parameters  
Placeholder	Value  
\<API\_VERSION\>

API version

\<IG\_USER\_ID\>

Required. App user's app-scoped user ID.

Query String Parameters  
Key	Placeholder	Value  
access\_token

\<ACCESS\_TOKEN\>

Required. App user's User access token.

catalog\_id

\<CATALOG\_ID\>

Required. ID of catalog to search.

q

\<QUERY\_STRING\>

A string to search for in each product's name or SKU number (SKU numbers can be added in the Content ID column in the catalog management interface). If no string is specified, all tag-eligible products will be returned.

Response  
A JSON-formatted object containing an array of tag-eligible products and their metadata. Supports cursor-based pagination.

{  
  "data": \[  
    {  
      "product\_id": {product-id},  
      "merchant\_id": {merchant-id},  
      "product\_name": "{product-name}",  
      "image\_url": "{image-url}",  
      "retailer\_id": "{retailer-id}",  
      "review\_status": "{review-status}",  
      "is\_checkout\_flow": {is-checkout-flow}  
    }  
  \]  
}  
Response Contents  
Property	Value  
product\_id

Product ID.

merchant\_id

Merchant ID.

product\_name

Product name.

image\_url

Product image URL.

retailer\_id

Retailer ID.

review\_status

Review status. Values can be approved, outdated, pending, rejected. An approved product can appear in the app user's Instagram Shop, but an approved status does not indicate product availability (e.g, the product could be out of stock). Only tags associated with products that have a review\_status of approved can appear on published posts.

is\_checkout\_flow

If true, product can be purchased directly in the Instagram app. If false, product must be purchased in the app user's app/website.

product\_variants

Product IDs (product\_id) and variant names (variant\_name) of product variants.

cURL Example  
Request  
curl \-i \-X GET \\  
 "https://graph.facebook.com/v24.0/90010177253934/catalog\_product\_search?catalog\_id=960179311066902\&q=gummy\&access\_token=EAAOc"  
Response  
{  
  "data": \[  
    {  
      "product\_id": 3231775643511089,  
      "merchant\_id": 90010177253934,  
      "product\_name": "Gummy Wombats",  
      "image\_url": "https://scont...",  
      "retailer\_id": "oh59p9vzei",  
      "review\_status": "approved",  
      "is\_checkout\_flow": true,  
      "product\_variants": \[  
            {  
              "product\_id": 5209223099160494  
            },  
            {  
              "product\_id": 7478222675582505,  
              "variant\_name": "Green Gummy Wombats"  
            }  
          \]  
    }  
  \]  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
IG User Connected Threads User  
Represents a Threads account connected to an Instagram account.

Requirements  
Type	Description  
Permissions

instagram\_basic  
threads\_business\_basic  
pages\_read\_engagement  
If the app user was granted a role via the Business Manager on the Page connected to your app user's Instagram professional account, your app will also need one of:

ads\_management  
ads\_read  
Tokens

A Facebook User access token.

Limitations  
You need to have at least an Advertiser role on the Page that is linked to your Instagram account; Manager or Content Creator also work. Or you need to have the Instagram account connected to a business account where you have appropriate roles.  
If you want to use this Threads account ID for Threads ads creation, make sure your connected Instagram account has the correct ads creation identity setup, either business-claimed Instagram account or page-connected Instagram account.  
An Instagram account can have only one Instagram-connected Threads account. Verify whether a specific Instagram account has an Instagram-connected Threads account before attempting to create a new one. If one already exists, use that one.  
Reading  
GET /{ig-user-id}/connected\_threads\_user

Once you connect a Threads account to a valid Instagram account, you can make an API request to get the Threads account ID.

Sample request  
curl \-G \\  
  \-d "access\_token=\<ACCESS\_TOKEN\>"\\  
  \-d "fields=threads\_user\_id" \\  
"https://graph.facebook.com/v24.0/\<IG\_USER\_ID\>/connected\_threads\_user"  
Sample response  
{  
  "data": \[  
    {  
      "threads\_user\_id": "\<THREADS\_USER\_ID\>",  
    }  
  \],  
}  
Creating  
This operation is not supported.

Updating  
This operation is not supported.

Deleting  
This operation is not supported.

"  
&  
"  
IG User Content Publishing Limit  
Represents an IG User's current content publishing usage.

Requirements  
Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User user access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_content\_publish  
instagram\_basic  
instagram\_content\_publish  
pages\_read\_engagement  
If the app user was granted a role via the Business Manager on the Page connected to the targeted IG User, you will also need one of:

ads\_management  
ads\_read  
Creating  
This operation is not supported.

Reading  
GET /\<IG\_USER\_ID\>/content\_publishing\_limit

Get the number of times an IG User has published and IG Container within a given time period. Refer to the Content Publishing guide for complete publishing steps.

Request Syntax  
GET https://graph.facebook.com/\&lt;API\_VERSION\>/\&lt;IG\_USER\_ID\>/content\_publishing\_limit  
  ?fields=\&lt;LIST\_OF\_FIELDS\>  
  \&since=\&lt;UNIX\_TIMESTAMP\>  
  \&access\_token=\&lt;ACCESS\_TOKEN\>  
Query String Parameters  
Placeholder	Value Description  
\<ACCESS\_TOKEN\>  
Required  
String

The app user's User Access Token.

\<LIST\_OF\_FIELDS\>  
Comma-separated list

A comma-separated list of fields you want returned. If omitted, the quota\_usage field will be returned by default.

\<UNIX\_TIMESTAMP\>  
Unix timestamp

A Unix timestamp no older than 24 hours.

Fields  
Field	Value Description  
config  
Object

Returns these values:

quota\_total — The maximum number of IG Containers the app user can publish within the quota\_duration time period (currently 50).  
quota\_duration — The period of time in seconds against which the quota\_total is calculated (currently 86400 seconds, or 24 hours).  
quota\_usage  
Comma-separated list

The number of times the app user has published an IG Container since the time specified in the since query string parameter. If the since parameter is omitted, this value will be the number of times the app user has published a container within the last 24 hours. This field is returned by default if the fields query string parameter is omitted from the query.

Example Request  
curl \-X GET \\  
  'https://graph.facebook.com/v24.0/17841405822304914/content\_publishing\_limit?fields=quota\_usage,rate\_limit\_settings\&since=1609969714\&access\_token=IGQVJ...'  
Example Response  
{  
  "data": \[  
    {  
      "quota\_usage": 2,  
      "config": {  
        "quota\_total": 50,  
        "quota\_duration": 86400  
      }  
    }  
  \]  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.  
"  
&  
"  
Instagram Account Insights  
Represents social interaction metrics on your app user's Instagram business or creator account.

In this guide, we use Instagram user and Instagram account interchangeably.

Available for the Instagram API with Facebook Login and Instagram API with Instagram Login.

The following metrics have been deprecated for v22.0 and will be deprecated for all versions on April 21, 2025:

impressions  
Introducing the new views metric with total\_value metric type and with breakdowns for follower\_type and media\_product\_type.

Visit the Instagram Platform Changelog for more information.

Creating  
This operation is not supported.

Reading  
GET /\<YOUR\_APP\_USERS\_INSTAGRAM\_ACCOUNT\_ID\>/insights

Returns insights on your app user's Instagram business or creator account.

Requirements  
Instagram API with Instagram Login	Instagram API with Facebook Login  
Access Tokens

Instagram User access token  
Facebook User access token  
Host URL

graph.instagram.com

graph.facebook.com

Login Type

Business Login for Instagram

Facebook Login for Business

Permissions	  
instagram\_business\_basic  
instagram\_business\_manage\_insights  
instagram\_basic  
instagram\_manage\_insights  
pages\_read\_engagement  
If the app user was granted a role on the Page connected to your app user's Instagram professional account via the Business Manager, your app will also need:

ads\_management  
ads\_read  
Limitations  
follower\_count and online\_followers metrics are not available on Instagram business or creator accounts with fewer than 100 followers.  
Insights data for the online\_followers metric is only available for the last 30 days.  
If insights data you are requesting does not exist or is currently unavailable, the API will return an empty data set instead of 0 for individual metrics.  
Demographic metrics only return the top 45 performers.  
Only viewers for whom we have demographic data are used in demographic metric calculations.  
Summing demographic metric values may result in a value less than the follower count (see previous bullet point).  
Data used to calculate metrics may be delayed up to 48 hours.  
Request Syntax  
GET https://\<HOST\_URL\>/\<API\_VERSION\>/\<APP\_USERS\_INSTAGRAM\_ACCOUNT\_ID\>/insights  
  ?metric=\<COMMA\_SEPARATED\_LIST\_OF\_METRICS\>  
  \&period=\<PERIOD\>  
  \&timeframe=\<TIMEFRAME\>  
  \&metric\_type=\<METRIC\_TYPE\>  
  \&breakdown=\<BREAKDOWN\_METRIC\>  
  \&since=\<START\_TIME\>  
  \&until=\<STOP\_TIME\>  
  \&access\_token=\<INSTAGRAM\_USER\_ACCESS\_TOKEN\>  
Host Path Parameters  
GET https://\<HOST\_URL\>/\<API\_VERSION\>/\<APP\_USERS\_INSTAGRAM\_ACCOUNT\_ID\>/insights  
Placeholder	Value  
\<API\_VERSION\>

The latest version is: v24.0	  
The API version your app is using when making calls to Meta servers. Learn more about API versioning.

\<APP\_USERS\_INSTAGRAM\_ACCOUNT\_ID\>

Required. The ID of your app user's Instagram professional account.

\<HOST\_URL\>

Required. The ID of your app user's Instagram professional account.

Parameters  
Key	Value  
access\_token

Required. The app user's Facebook User or Instagram access token.

breakdown

Designates how to break down result set into subsets.

contact\_button\_type – Divides results by profile component in the native app.  
follow\_type – Breaks down results by followers or non-followers.  
media\_product\_type – Breaks down results by surface where Instagram users view or interact with your app user's media.  
metric

Required. Comma-separated list of Metrics you want returned.

\<COMMA\_SEPARATED\_LIST\_OF\_METRICS\>

metric\_type

Designates if you want the responses aggregated by time period or as a simple total. See Metric Type. \<METRIC\_TYPE\>

period

Required. Period aggregation. \<PERIOD\>

since

Unix timestamp indicating start of range. See Range. \<START\_TIME\>

timeframe

Required for demographics-related metrics. Designates how far to look back for data. See Timeframe. \<TIMEFRAME\>

until

Unix timestamp indicating end of range. See Range. \<STOP\_TIME\>

Breakdown  
If you request metric\_type=total\_value, you can also specify one or more breakdowns, and the results will be broken down into smaller sets based on the specified breakdown. Values can be:

contact\_button\_type — Break down results by profile UI component that viewers tapped or clicked. Response values can be:  
BOOK\_NOW  
CALL  
DIRECTION  
EMAIL  
INSTANT\_EXPERIENCE  
TEXT  
UNDEFINED  
follow\_type — Break down results by followers or non-followers. Response values can be:  
FOLLOWER  
NON\_FOLLOWER  
UNKNOWN  
media\_product\_type — Break down results by the surface where viewers viewed or interacted with the app user's media. Response values can be:  
AD  
FEED  
REELS  
STORY  
Refer to the Metrics table to determine which metrics are compatible with a breakdown. If you request a metric that doesn't support a breakdown, the API will return an error ("An unknown error has occurred."), so be careful if requesting multiple metrics in a single query.

If you request metric\_type=time\_series, breakdowns will not be included in the response.

Metric Type  
You can designate how you want results aggregated, either by time period or as a simple total (with breakdowns, if requested). Values can be:

time\_series — Tells the API to aggregate results by time period. See Period.  
total\_value — Tells the API to return results as a simple total. If breakdowns are included in the request, the result set will be further broken down by the specific breakdowns. See Breakdown.  
Period  
Tells the API which time frame to use when aggregating results. Only compatible with interaction-related metrics.

Timeframe  
Tells the API how far to look back for data when requesting demographic-related metrics. This value overrides the since and until parameters.

Range  
Assign UNIX timestamps to the since and until parameters to define a range. The API will only include data created within this range (inclusive). If you do not include these parameters, the API will look back 24 hours.

For demographics-related metrics, the timeframe parameter overrides these values. See Timeframe.

Metrics  
Interaction Metrics

Metric	Period	Timeframe	Breakdown	Metric Type	Description  
accounts\_engaged

day

n/a

n/a

total\_value

The number of accounts that have interacted with your content, including in ads. Content includes posts, stories, reels, videos and live videos. Interactions can include actions such as likes, saves, comments, shares or replies.

This metric is estimated.

comments

day

n/a

media\_product\_type

total\_value

The number of comments on your posts, reels, videos and live videos.

This metric is in development.

engaged\_audience\_demographics

lifetime

One of:

last\_14\_days, last\_30\_days, last\_90\_days, prev\_month, this\_month, this\_week

age,  
city,  
country,  
gender

total\_value

The demographic characteristics of the engaged audience, including countries, cities and gender distribution. this\_month returns the data in the last 30 days and this\_week returns data in the last 7 days.

Does not support since or until. See Range for more information.

Not returned if the IG User has less than 100 engagements during the timeframe.

Note: The last\_14\_days, last\_30\_days, last\_90\_days and prev\_month timeframes will no longer be supported beginning with v20.0. See the changelog for more information.

follows\_and\_unfollows

day

n/a

follow\_type

total\_value

The number of accounts that followed you and the number of accounts that unfollowed you or left Instagram in the selected time period.

Not returned if the IG User has less than 100 followers.

follower\_demographics

lifetime

One of:

last\_14\_days, last\_30\_days, last\_90\_days, prev\_month, this\_month, this\_week

age,  
city,  
country,  
gender

total\_value

The demographic characteristics of followers, including countries, cities and gender distribution.

Does not support since or until. See Range for more information.

Not returned if the IG User has less than 100 followers.

impressions Deprecated for v22.0+ and all versions April 21, 2025\.

day

n/a

n/a

total\_value, time\_series

The number of times your posts, stories, reels, videos and live videos were on screen, including in ads.

likes

day

n/a

media\_product\_type

total\_value

The number of likes on your posts, reels, and videos.

profile\_links\_taps

day

n/a

contact\_button\_type

total\_value

The number of taps on your business address, call button, email button and text button.

reach

day

n/a

media\_product\_type, follow\_type

total\_value, time\_series

The number of unique accounts that have seen your content, at least once, including in ads. Content includes posts, stories, reels, videos and live videos. Reach is different from impressions, which may include multiple views of your content by the same accounts.

This metric is estimated.

replies

day

n/a

n/a

total\_value

The number of replies you received from your story, including text replies and quick reaction replies.

saved

day

n/a

media\_product\_type

total\_value

The number of saves of your posts, reels, and videos.

shares

day

n/a

media\_product\_type

total\_value

The number of shares of your posts, stories, reels, videos and live videos.

total\_interactions

day

n/a

media\_product\_type

total\_value

The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.

views

day

n/a

follower\_type, media\_product\_type

total\_value

The number of times your content was played or displayed. Content includes reels, posts, stories.

This metric is in development.

Response  
A JSON object containing the results of your query. Results can include the following data, based on your query specifications:

{  
  "data": \[  
    {  
      "name": "{data}",  
      "period": "\<PERIOD\>",  
      "title": "{title}",  
      "description": "{description}",  
      "total\_value": {  
        "value": {value},  
        "breakdowns": \[  
          {  
            "dimension\_keys": \[  
              "{key-1}",  
              "{key-2",  
              ...  
            \],  
            "results": \[  
              {  
                "dimension\_values": \[  
                  "{value-1}",  
                  "{value-2}",  
                  ...  
                \],  
                "value": {value},  
                "end\_time": "{end-time}"  
              },  
              ...  
            \]  
          }  
        \]  
      },  
      "id": "{id}"  
    }  
  \],  
  "paging": {  
    "previous": "{previous}",  
    "next": "{next}"  
  }  
}  
Response Contents  
Property	Value Type	Description  
breakdowns

Array

An array of objects describing the breakdowns requested and their results.

Only returned if metric\_type=total\_values is requested.

data

Array

An array of objects describing your results.

description

String

Metric description.

dimension\_keys

Array

An array of strings describing breakdowns requested in the query. Can be used as keys corresponding to values in individual breakdown sets.

Only returned if metric\_type=total\_values is requested.

dimension\_values

Array

An array of strings describing breakdown set values. Values can be mapped to dimension\_keys.

Only returned if metric\_type=total\_values is requested.

end\_time

String

ISO 8601 timestamp with time and offset. For example: 2022-08-01T07:00:00+0000

id

String

A string describing the query's path parameters.

name

String

Metric requested.

next

String

URL to retrieve the next page of results. See Paginated Results for more information.

paging

Object

An object containing URLs used to request the next set of results. See Paginated Results for more information.

period

String

Period requested.

previous

String

URL to retrieve the previous page of results. See Paginated Results for more information.

results

Array

An array of objects describing each breakdown set.

Only returned if metric\_type=total\_values is requested.

title

String

Metric title.

total\_value

Object

Object describing requested breakdown values (if breakdowns were requested).

value

Integer

For data.total\_value.value, sum of requested metric values.

For data.total\_value.breakdowns.results.value, sum of breakdown set values. Only returned if metric\_type=total\_values is requested.

Examples  
Interaction Metrics  
curl \-i \-X GET \\  
  "https://graph.facebook.com/v24.0/17841405822304914/insights?metric=reach\&period=day\&breakdown=media\_product\_type\&metric\_type=total\_value\&since=1658991600\&access\_token=EAAOc..."  
Response  
{  
  "data": \[  
    {  
      "name": "reach",  
      "period": "day",  
      "title": "Accounts reached",  
      "description": "The number of unique accounts that have seen your content, at least once, including in ads. Content includes posts, stories, reels, videos and live videos. Reach is different from impressions, which may include multiple views of your content by the same accounts. This metric is estimated and in development.",  
      "total\_value": {  
        "value": 224,  
        "breakdowns": \[  
          {  
            "dimension\_keys": \[  
              "media\_product\_type"  
            \],  
            "results": \[  
              {  
                "dimension\_values": \[  
                  "CAROUSEL\_CONTAINER"  
                \],  
                "value": 100  
              },  
              {  
                "dimension\_values": \[  
                  "POST"  
                \],  
                "value": 124  
              }  
            \]  
          }  
        \]  
      },  
      "id": "17841405309211844/insights/reach/day"  
    }  
  \],  
  "paging": {  
    "previous": "https://graph.face...",  
    "next": "https://graph.face..."  
  }  
Demographic Metrics  
curl \-i \-X GET \\  
  "https://graph.facebook.com/v24.0/17841405822304914/insights?metric=engaged\_audience\_demographics\&period=lifetime\&timeframe=last\_90\_days\&breakdowns=country\&metric\_type=total\_value\&access\_token=EAAOc..."  
Response  
{  
  "data": \[  
    {  
      "name": "engaged\_audience\_demographics",  
      "period": "lifetime",  
      "title": "Engaged audience demographics",  
      "description": "The demographic characteristics of the engaged audience, including countries, cities and gender distribution.",  
      "total\_value": {  
        "breakdowns": \[  
          {  
            "dimension\_keys": \[  
              "timeframe",  
              "country"  
            \],  
            "results": \[  
              {  
                "dimension\_values": \[  
                  "LAST\_90\_DAYS",  
                  "AR"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "LAST\_90\_DAYS",  
                  "RU"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "LAST\_90\_DAYS",  
                  "MA"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "LAST\_90\_DAYS",  
                  "LA"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "LAST\_90\_DAYS",  
                  "IQ"  
                \],  
                "value": 2  
              },  
              {  
                "dimension\_values": \[  
                  "LAST\_90\_DAYS",  
                  "MX"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "LAST\_90\_DAYS",  
                  "FR"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "LAST\_90\_DAYS",  
                  "ES"  
                \],  
                "value": 3  
              },  
              {  
                "dimension\_values": \[  
                  "LAST\_90\_DAYS",  
                  "NL"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "LAST\_90\_DAYS",  
                  "TR"  
                \],  
                "value": 1  
              },  
              {  
                "dimension\_values": \[  
                  "LAST\_90\_DAYS",  
                  "US"  
                \],  
                "value": 7  
              }  
            \]  
          }  
        \]  
      },  
      "id": "17841401130346306/insights/engaged\_audience\_demographics/lifetime"  
    }  
  \]  
}  
Updating  
This operation is not supported.

Deleting  
This operation is not supported.  
"

**IG User Instagram-Backed Threads User**  
Represents a [Threads account backed by an Instagram account](https://developers.facebook.com/docs/marketing-api/ad-creative/threads-ads#instagram-backed-threads-accounts).

You cannot log into Instagram-backed Threads accounts to manage posts.

### **Requirements**

| Type | Description |
| ----- | ----- |
| [Permissions](https://developers.facebook.com/docs/apps/review/login-permissions) | [instagram\_basic](https://developers.facebook.com/docs/facebook-login/permissions#instagram_basic) [threads\_business\_basic](https://developers.facebook.com/docs/facebook-login/permissions#threads_business_basic) [pages\_read\_engagement](https://developers.facebook.com/docs/permissions#pages_read_engagement) If the app user was granted a role via the Business Manager on the [Page](https://developers.facebook.com/docs/instagram-api/overview#pages) connected to your app user's Instagram professional account, your app will also need one of: [ads\_management](https://developers.facebook.com/docs/facebook-login/permissions#ads_management) [ads\_read](https://developers.facebook.com/docs/facebook-login/permissions#ads_read) |
| [Tokens](https://developers.facebook.com/docs/facebook-login/access-tokens) | A Facebook User [access token](https://developers.facebook.com/docs/instagram-api/overview#authentication). |

### **Limitations**

* You need to have at least an Advertiser role on the [Page that is linked to your Instagram account](https://developers.facebook.com/docs/instagram/ads-api/guides/pages-ig-account#via_page); Manager or Content Creator also work. Or you need to have the Instagram account [connected to a business account](https://developers.facebook.com/docs/instagram/ads-api/guides/ig-accounts-with-business-manager#claim_account) where you have appropriate roles.  
* If you want to use this Threads account ID for Threads ads creation, make sure your Instagram account has the correct ads creation identity setup, either [business-claimed Instagram account](https://developers.facebook.com/docs/instagram/ads-api/guides/ig-accounts-with-business-manager) or [page-connected Instagram account](https://developers.facebook.com/docs/instagram/ads-api/guides/pages-ig-account).  
* An Instagram account can have only one Instagram-backed Threads account. [Verify whether a specific Instagram accounts has an Instagram-backed Threads account](https://developers.facebook.com/docs/instagram-platform/instagram-graph-api/reference/ig-user/instagram_backed_threads_user#reading) before attempting to create a new one. If one already exists, use that one.

## **Reading**

GET /{ig-user-id}/instagram\_backed\_threads\_user

You can make an API request to get the Instagram-backed Threads account ID.

### **Sample request**

curl \-G \\  
  \-d "access\_token=\<ACCESS\_TOKEN\>"\\  
  \-d "fields=threads\_user\_id" \\

"https://graph.facebook.com/v24.0/\<IG\_USER\_ID\>/instagram\_backed\_threads\_user"

### **Sample response**

{  
  "data": \[  
    {  
      "threads\_user\_id": "\<THREADS\_USER\_ID\>",  
    }  
  \],

}

## **Creating**

POST /{ig-user-id}/instagram\_backed\_threads\_user

You can make an API call to create an Instagram-backed Threads account specifically for running ads on Threads.

### **Sample request**

curl \\  
  \-F "access\_token=\<ACCESS\_TOKEN\>"\\

"https://graph.facebook.com/v24.0/\<IG\_USER\_ID\>/instagram\_backed\_threads\_user"

### **Sample response**

{  
  "data": \[  
    {  
      "threads\_user\_id": "\<THREADS\_USER\_ID\>",  
    }  
  \],

}

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **IG User Live Media**

Represents a collection of live video [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) on an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user).

## **Creating**

This operation is not supported.

## **Reading**

GET /{ig-user-id}/live\_media

Get a collection of live video [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) on an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user).

### **Limitations**

Only live video IG Media being broadcast at the time of the request will be returned.

### **Time-based Pagination**

This endpoint supports [time-based pagination](https://developers.facebook.com/docs/graph-api/using-graph-api#time). Include since and until query-string paramaters with Unix timestamp or strtotime data values to define a time range.

### **Requirements**

| Type | Requirement |
| ----- | ----- |
| Access Tokens | User |
| [Permissions](https://developers.facebook.com/docs/permissions) | instagram\_basic pages\_read\_engagement If the app user was granted a role via the Business Manager on the [Page](https://developers.facebook.com/docs/instagram-api/overview#pages) connected to the targeted IG User, you will also need one of: ads\_management ads\_read |

### **Request Syntax**

GET https://graph.facebook.com/{api-version}/{ig-user-id}/live\_media

  ?access\_token={access-token}

### **Path Parameters**

| Placeholder | Value |
| ----- | ----- |
| {api-version}*String* | API version |
| {ig-user-id}Required*String* | App user's app-scoped user ID. |

### **Query String Parameters**

| Key | Value |
| ----- | ----- |
| access\_tokenRequired*String* | App user's User |
| fields*Comma-separated list* | Comma-separated list of IG Media [fields](https://developers.facebook.com/docs/instagram-api/reference/ig-media#fields) you want returned for each live IG Media in the result set. |
| since*timestamp* | A Unix timestamp or strtotime data value that points to the start of a range of time-based data. See [time-based pagination](https://developers.facebook.com/docs/graph-api/using-graph-api#time). |
| until*timestamp* | A Unix timestamp or strtotime data value that points to the end of a range of time-based data. See [time-based pagination](https://developers.facebook.com/docs/graph-api/using-graph-api#time). |

### **Response**

A JSON-formatted object containing the data you requested.

{  
  "data": \[\],  
  "paging": {}

}

#### **Response Contents**

| Property | Value |
| ----- | ----- |
| data | An array of [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) on an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user). |
| paging | An object containing [paging](https://developers.facebook.com/docs/graph-api/using-graph-api#paging) cursors and next/previous data set retrievial URLs. |

### **cURL Example**

#### **Request**

curl \-X GET \\

  'https://graph.facebook.com/v24.0/17841405822304914/live\_media?fields=id,media\_type,media\_product\_type,owner,username,comments\&access\_token=IGQVJ...'

#### **Response**

{  
   "id":"90010498116233",  
   "media\_type":"BROADCAST",  
   "media\_product\_type":"LIVE",  
   "owner":{  
      "id":"17841405822304914"  
   },  
   "username":"jayposiris",  
   "comments":{  
      "data":\[  
        {  
            "hidden": false,  
            "id": "17907364514064687",  
            "like\_count": 0,  
            "media": {  
                "id": "17892642502701087"  
            },  
            "text": "@jayposiris",  
            "timestamp": "2021-08-17T21:23:07+0000",  
            "username": "bztest0316\_11",  
            "from": {  
                "id": "5895605157123796",  
                "username": "bztest0316\_11"  
            }  
        }  
      \]  
   }

}

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **IG User Media**

Represents a collection of [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) objects on an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user).

On July 9, 2025, we added support for the existing user\_tags field for image and video stories on the /\<IG\_ID\>/media endpoint. You can mention users in a story and optionally specify x, y coordinates to tag them at a particular coordinate in the media.

On March 24, 2025, we introduced the new alt\_text field for image posts on the /\<INSTAGRAM\_PROFESSIONAL\_ACCOUNT\_ID\>/media endpoint. Reels and stories are not supported.

## **Creating**

POST /\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media

* Create an image, carousel, story or reel [IG Container](https://developers.facebook.com/docs/instagram-api/reference/ig-container) for use in the post publishing process. See the [Content Publishing](https://developers.facebook.com/docs/instagram-api/guides/content-publishing) guide for complete publishing steps.

Steps to publish a media object include the following:

1. Create a container  
2. Upload the media to the container  
3. Publish the container

### **Limitations**

#### **General Limitations**

* Containers expire after 24 hours  
* An Instagram account can only create 400 containers within a rolling 24 hour period  
* If the [Page](https://developers.facebook.com/docs/instagram-api/overview#pages) connected to the targeted Instagram professional account requires [Page Publishing Authorization](https://www.facebook.com/help/www/1939753742723975) (PPA), PPA must be completed or the request will fail  
* If the Page connected to the targeted Instagram professional account requires two-factor authentication, the Facebook User must also have performed two-factor authentication or the request will fail  
* We strongly recommended the HTTP IETF standard character set for URLs, URLs that contain only US ASCII characters, or the request will fail

#### **Reels Limitations**

* Reels cannot appear in carousels  
* Account privacy settings are respected upon publish. For example, if Allow remixing is enabled, published reels will have remixing enabled upon publish but remixing can be disabled on published reels manually through the Instagram app.  
* Music tagging is only available for original audio.

#### **Story Limitations**

* Stories expire after 24 hours.  
* Support either video URL or Reels URL but not both.  
* Publishing stickers (i.e., link, poll, location) is not supported; however mentioning users without a sticker is supported.

### **Requirements**

| Type | Description |
| ----- | ----- |
| [Access Tokens](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) | [User](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) |
| [Business Roles](https://www.facebook.com/business/help/442345745885606) | If creating containers for [product tagging](https://developers.facebook.com/docs/instagram-api/guides/product-tagging), the app user must have an admin role on the [Business Manager](https://business.facebook.com/) that owns the IG User's [Instagram Shop](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F1187859655048322%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEeLnNZIi6Ad6LbFhwmP3luZ0DlkWKQHnDnsUkvFXRBjhV3YFG2CWiuRWXDeLU_aem_0c7uoXwCjXqj-twb6AjTTA&h=AT1828053uwUzGJLcafvAh9abF9xP9XfaxckHEUlQTvWdPkTU6bHpiq2kdN_tdpVRp4fx4gPW-IHoDyuNTd7kP2UCiMwzAiwtH9-T9lieQTNsgugy7YAUPr6bL8FlQnyZ8hlWWmq8Ro). |
| [Permissions](https://developers.facebook.com/docs/apps/review/login-permissions) | [instagram\_basic](https://developers.facebook.com/docs/facebook-login/permissions#reference-instagram_basic)[instagram\_content\_publish](https://developers.facebook.com/docs/permissions/reference/instagram_content_publish)[pages\_read\_engagement](https://developers.facebook.com/docs/facebook-login/permissions#reference-pages_read_engagement) If the app user was granted a role on the Page via the Business Manager, you will also need one of: [ads\_management](https://developers.facebook.com/docs/permissions/reference/ads_management)ads\_read If creating containers for [product tagging](https://developers.facebook.com/docs/instagram-api/guides/product-tagging), you will also need: [catalog\_management](https://developers.facebook.com/docs/permissions/reference/catalog_management)[instagram\_shopping\_tag\_products](https://developers.facebook.com/docs/permissions/reference/instagram_shopping_tag_products) |
| [Tasks](https://developers.facebook.com/docs/instagram-api/overview#tasks) | Your app user must be able to perform the MANAGE or CREATE\_CONTENT tasks on the Page linked to their Instagram professional account. |

### **Image Specifications**

* Format: JPEG  
* File size: 8 MB maximum.  
* Aspect ratio: Must be within a 4:5 to 1.91:1 range  
* Minimum width: 320 (will be scaled up to the minimum if necessary)  
* Maximum width: 1440 (will be scaled down to the maximum if necessary)  
* Height: Varies, depending on width and aspect ratio  
* Color Space: sRGB. Images using other color spaces will have their color spaces converted to sRGB.

### **Reel Specifications**

The following are the specifications for Reels:

* Container: MOV or MP4 (MPEG-4 Part 14), no edit lists, moov atom at the front of the file.  
* Audio codec: AAC, 48khz sample rate maximum, 1 or 2 channels (mono or stereo).  
* Video codec: HEVC or H264, progressive scan, closed GOP, 4:2:0 chroma subsampling.  
* Frame rate: 23-60 FPS.  
* Picture size:  
  * Maximum columns (horizontal pixels): 1920  
  * Required aspect ratio is between 0.01:1 and 10:1 but we recommend 9:16 to avoid cropping or blank space.  
* Video bitrate: VBR, 25Mbps maximum  
* Audio bitrate: 128kbps  
* Duration: 15 mins maximum, 3 seconds minimum  
* File size: 300MB maximum

The following are the specifications for a Reels cover photo:

* Format: JPEG  
* File size: 8MB maximum  
* Color Space: sRGB. Images that use other color spaces will be converted to sRGB.  
* Aspect ratio: We recommend 9:16 to avoid cropping or blank space. If the aspect ratio of the original image is not 9:16, we crop the image and use the middle most 9:16 rectangle as the cover photo for the reel. If you share a reel to your feed, we crop the image and use the middle most 1:1 square as the cover photo for your feed post.

### **Story Image Specifications**

* Format: JPEG  
* File size: 8 MB maximum.  
* Aspect ratio: We recommended 9:16 to avoid cropping or blank space  
* Color Space: sRGB. Images using other color spaces will have their color spaces converted to sRGB

### **Story Video Specifications**

* Container: MOV or MP4 (MPEG-4 Part 14), no edit lists, moov atom at the front of the file.  
* Audio codec: AAC, 48khz sample rate maximum, 1 or 2 channels (mono or stereo).  
* Video codec: HEVC or H264, progressive scan, closed GOP, 4:2:0 chroma subsampling.  
* Frame rate: 23-60 FPS.  
* Picture size:  
  * Maximum columns (horizontal pixels): 1920  
  * Required aspect ratio is between 0.1:1 and 10:1 but we recommend 9:16 to avoid cropping or blank space  
* Video bitrate: VBR, 25Mbps maximum  
* Audio bitrate: 128kbps  
* Duration: 60 seconds maximum, 3 seconds minimum  
* File size: 100MB maximum

### **Request Syntax**

#### **Image Containers**

POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_IG\_USER\_ID\>/media  
?image\_url=\<IMAGE\_URL\>  
\&is\_carousel\_item=\<TRUE\_OR\_FALSE\>  
\&alt\_text=\<IMAGE\_ALTERNATIVE\_TEXT\>        
\&caption=\<IMAGE\_CAPTION\>  
\&location\_id=\<LOCATION\_PAGE\_ID\>  
\&user\_tags=\<ARRAY\_OF\_USERS\_FOR\_TAGGING\>\>  
\&product\_tags=\<ARRAY\_OF\_PRODUCTS\_FOR\_TAGGING\>

\&access\_token=\<USER\_ACCESS\_TOKEN\>

#### **Reel Containers**

##### ***Standard upload***

POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?media\_type=REELS  
\&video\_url=\<REEL\_URL\>  
\&caption=\<IMAGE\_CAPTION\>  
\&share\_to\_feed=\<TRUE\_OR\_FALSE\>  
\&collaborators=\<COLLABORATOR\_USERNAMES\>  
\&cover\_url=\<COVER\_URL\>  
\&audio\_name=\<AUDIO\_NAME\>  
\&user\_tags=\<ARRAY\_OF\_USERS\_FOR\_TAGGING\>\>  
\&location\_id=\<LOCATION\_PAGE\_ID\>  
\&thumb\_offset=\<THUMB\_OFFSET\>  
\&share\_to\_feed=\<TRUE\_OR\_FALSE\>

\&access\_token=\<USER\_ACCESS\_TOKEN\>

##### ***Resumable upload session***

POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?media\_type=REELS  
\&upload\_type=resumable  
\&caption=\<IMAGE\_CAPTION\>  
\&collaborators=\<COLLABORATOR\_USERNAMES\>  
\&cover\_url=\<COVER\_URL\>  
\&audio\_name=\<AUDIO\_NAME\>  
\&user\_tags=\<ARRAY\_OF\_USERS\_FOR\_TAGGING\>\>  
\&location\_id=\<LOCATION\_PAGE\_ID\>  
\&thumb\_offset=\<THUMB\_OFFSET\>

\&access\_token=\<USER\_ACCESS\_TOKEN\>

On success, an ig-container-id and a uri is returned in the response, which will be used in subsequent steps, such as:

{  
  "id": "\<IG\_CONTAINER\_ID\>",  
  "uri": "https://rupload.facebook.com/ig-api-upload/v24.0/\<IG\_CONTAINER\_ID\>"

}

#### **Carousel Containers**

Carousel containers only. To create carousel item containers, create image or video containers instead (reels are not supported). See [Carousel Posts](https://developers.facebook.com/docs/instagram-api/guides/content-publishing#carousel-posts) for complete publishing steps.

##### ***Standard upload***

POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?media\_type=CAROUSEL  
\&caption=\<IMAGE\_CAPTION\>  
\&share\_to\_feed=\<TRUE\_OR\_FALSE\>  
\&collaborators=\<COLLABORATOR\_USERNAMES\>  
\&location\_id=\<LOCATION\_PAGE\_ID\>  
\&product\_tags=\<ARRAY\_OF\_PRODUCTS\_FOR\_TAGGING\>  
\&children=\<ARRAY\_OF\_CAROUSEL\_CONTAINTER\_IDS\>

\&access\_token=\<USER\_ACCESS\_TOKEN\>

##### ***Resumable upload session***

POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?media\_type=VIDEO  
\&is\_carousel\_item=true  
\&upload\_type=resumable

\&access\_token=\<USER\_ACCESS\_TOKEN\>

On success, an ig-container-id and a uri is returned in the response, which will be used in subsequent steps.

#### **Image Story Containers**

POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?image\_url=\<IMAGE\_URL\>  
\&media\_type=STORIES  
\&user\_tags=\<ARRAY\_OF\_USERS\_FOR\_TAGGING\>

\&access\_token=\<USER\_ACCESS\_TOKEN\>

#### **Video Story Containers**

##### ***Standard upload***

POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?video\_url=\<VIDEO\_URL\>  
\&media\_type=STORIES  
\&user\_tags=\<ARRAY\_OF\_USERS\_FOR\_TAGGING\>

\&access\_token=\<USER\_ACCESS\_TOKEN\>

##### ***Resumable upload session***

POST https://graph.facebook.com/v24.0/\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media  
?media\_type=STORIES  
\&upload\_type=resumable

\&access\_token=\<USER\_ACCESS\_TOKEN\>

On success, an Instagram container ID and a URI is returned in the response, which will be used in subsequent steps.

#### **Upload a video through resumable upload protocol**

Once the Instagram container ID returns from a resumable upload session call, send a POST request to the https://rupload.facebook.com/ig-api-upload/ v24.0/\<IG\_CONTAINER\_ID\> endpoint.

* All media\_type shares the same flow to upload the video.  
* ig-container-id is the ID from the resumable reels, carousel and video container upload session examples above.  
* access-token is the same one used in other steps.  
* offset is set to the first byte being upload, generally 0.  
* file\_size is set to the size of your file in bytes.  
* Your\_file\_local\_path sets to the file path of your local file, for example, if uploading a file from the Downloads folder on macOS, the path is @Downloads/example.mov.

curl \-X POST "https://rupload.facebook.com/ig-api-upload/v24.0/\<IG\_CONTAINER\_ID\>" \\  
     \-H "Authorization: OAuth \<USER\_ACCESS\_TOKEN\>" \\  
     \-H "offset: 0" \\  
     \-H "file\_size: Your\_file\_size\_in\_bytes" \\

     \--data-binary "@Your\_local\_file\_path.extension"

On success, you should see response like this example:

{  
  "success":true,  
  "message":"Upload successful. ..."  
}


#### **Upload a video from a hosted URL**

This service can also support video upload from a hosted URL.

curl \-X POST "https://rupload.facebook.com/ig-api-upload/v24.0/\<IG\_CONTAINER\_ID\>" \\  
     \-H "Authorization: OAuth \<USER\_ACCESS\_TOKEN\>" \\

     \-H "file\_url: \<VIDEO\_URL\>"

### **Path Parameters**

| Placeholder | Value |
| ----- | ----- |
| \<LATEST\_API\_VERSION\> The lastest API version is: v24.0 | API [version](https://developers.facebook.com/docs/graph-api/guides/versioning). |
| \<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>Required | App user's app-scoped user ID. |

### **Query String Parameters**

| Key | Placeholder | Description |
| ----- | ----- | ----- |
| access\_token | \<USER\_ACCESS\_TOKEN\> | Required. App user's [User](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) access token. |
| alt\_text | \<IMAGE\_ALTERNATIVE\_TEXT\> | For image posts only. Alternative text, up to 1000 character, for an image. Only supported on a single image or image media in a carousel. Reels and stories are not supported. |
| audio\_name | \<AUDIO\_NAME\> | For Reels only. Name of the audio of your Reels media. You can only rename once, either while creating a reel or after from the audio page. |
| caption | \<IMAGE\_CAPTION\> | A caption for the image, video, or carousel. Can include hashtags (example: \#crazywildebeest) and usernames of Instagram users (example: @natgeo). @Mentioned Instagram users receive a notification when the container is published. Maximum 2200 characters, 30 hashtags, and 20 @ tags. Not supported on images or videos in carousels. |
| collaborators | \<LIST\_OF\_COLLABORATORS\> | For Feed image, Reels and Carousels only. A list of up to 3 instagram usernames as collaborators on an ig media. Not supported for Stories. |
| children | \<ARRAY\_OF\_CAROUSEL\_CONTAINTER\_IDS | Required for carousels. Applies only to carousels. An array of up to 10 container IDs of each image and video that should appear in the published carousel. Carousels can have up to 10 total images, vidoes, or a mix of the two. |
| cover\_url | \<COVER\_URL\> | For Reels only. The path to an image to use as the cover image for the Reels tab. We will cURL the image using the URL that you specify so the image must be on a public server. If you specify both cover\_url and thumb\_offset, we use cover\_url and ignore thumb\_offset. The image must conform to the [specifications for a Reels cover photo](https://developers.facebook.com/docs/instagram-platform/instagram-graph-api/reference/ig-user/media#reels-specs). |
| image\_url | \<IMAGE\_URL\> | For images only and required for images. The path to the image. We will cURL the image using the URL that you specify so the image must be on a public server. |
| is\_carousel\_item | \<TRUE\_OR\_FALSE\> | Applies only to images and video. Set to true. Indicates image or video appears in a carousel. |
| location\_id | \<LOCATION\_PAGE\_ID\> | The ID of a [Page](https://developers.facebook.com/docs/graph-api/reference/page) associated with a location that you want to tag the image or video with. Use the [Pages Search API](https://developers.facebook.com/docs/pages/searching) to search for [Pages](https://developers.facebook.com/docs/graph-api/reference/page) whose names match a search string, then parse the results to identify any Pages that have been created for a physical location. Include the location field in your query and verify that the Page you want to use has location data. Attempting to create a container using a Page that has no location data will fail with coded exception INSTAGRAM\_PLATFORM\_API\_\_INVALID\_LOCATION\_ID. Not supported on images or videos in carousels. |
| media\_type | \<MEDIA\_TYPE\> | Required for carousels, stories, and reels. Indicates container is for a carousel, story or reel. Value can be: CAROUSEL REELS STORIES |
| product\_tags | \<ARRAY\_OF\_PRODUCTS\_FOR\_TAGGING\> | Required for product tagging. Applies only to images and videos. An array of objects specifying which product tags to tag the image or video with (maximum of 5; tags and product IDs must be unique). Each object should have the following information: product\_id — Required. Product ID. x — Images only. An optional float that indicates percentage distance from left edge of the published media image. Value must be within 0.0–1.0 range. y — Images only. An optional float that indicates percentage distance from top edge of the published media image. Value must be within 0.0–1.0 range. For example: \[{product\_id:'3231775643511089',x: 0.5,y: 0.8}\] |
| share\_to\_feed | \<TRUE\_OR\_FALSE\> | For Reels only. When true, indicates that the reel can appear in both the Feed and Reels tabs. When false, indicates the reel can only appear in the Reels tab. Neither value determines whether the reel actually appears in the Reels tab because the reel may not meet eligibilty requirements or may not be selected by our algorithm. See [reel specifications](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media#reel-specifications) for eligibility critera. |
| thumb\_offset | \<THUMB\_OFFSET\> | For videos and reels. Location, in milliseconds, of the video or reel frame to be used as the cover thumbnail image. The default value is 0, which is the first frame of the video or reel. For reels, if you specify both cover\_url and thumb\_offset, we use cover\_url and ignore thumb\_offset. |
| upload\_type | \<UPLOAD\_TYPE\> | An optional parameter for users want to upload video through the rupload protocol, values can be set to lowercase string value: resumable. |
| user\_tags | \<ARRAY\_OF\_USERS\_FOR\_TAGGING\>\> | Required for user tagging in images, videos, and stories. Videos in carousels are not supported. An array of public usernames and x/y coordinates for any public Instagram users who you want to tag in the image. Each object in the array should have the following information: username — Required. Username. x — Required for images, optional for stories. Applies only to images and stories. A float that indicates percentage distance from left edge of the published media image. Value must be within 0.0–1.0 range. y — Required for images, optional for stories. Applies only to images and stories. A float that indicates percentage distance from top edge of the published media image. Value must be within 0.0–1.0 range. |
| video\_url | \<VIDEO\_URL\> | Required for videos and reels. Applies only to videos and reels. Path to the video. We cURL the video using the passed-in URL, so it must be on a public server. |

### **Response**

A JSON-formatted object containing an [IG Container](https://developers.facebook.com/docs/instagram-api/reference/ig-container) ID which you can use to [publish](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media_publish) the container.

Video uploads are asynchronous, so receiving a container ID does not guarantee that the upload was successful. To verify that a video has been uploaded, request the [status\_code](https://developers.facebook.com/docs/instagram-api/reference/ig-container#fields) field on the IG Container. If its value is FINISHED, the video was uploaded successfully.

{  
  "id":"\<IG\_CONTAINER\_ID\>"

}

### **Sample Request**

POST graph.facebook.com/17841400008460056/media  
  ?image\_url=curls//www.example.com/images/bronzed-fonzes.jpg  
  \&caption=\#BronzedFonzes\!  
  \&collaborators= \[‘username1’,’username2’\]  
  \&user\_tags=\[  
    {  
      username:'kevinhart4real',  
      x: 0.5,  
      y: 0.8  
    },  
    {  
      username:'therock',  
      x: 0.3,  
      y: 0.2  
    }

  \]

### **Sample Response**

{  
  "id": "17889455560051444"

}

## **Reading**

GET /\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/media

Get all [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) on an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user).

### **Limitations**

* Returns a maximum of 10K of the most recently created media.  
* Story IG Media not supported, use the [GET /\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ID\>/stories](https://developers.facebook.com/docs/instagram-api/reference/ig-user/stories) endpoint instead.

### **Requirements**

| Type | Description |
| ----- | ----- |
| [Access Tokens](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) | [User](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) |
| [Permissions](https://developers.facebook.com/docs/apps/review/login-permissions) | [instagram\_basic](https://developers.facebook.com/docs/facebook-login/permissions#reference-instagram_basic)[pages\_read\_engagement](https://developers.facebook.com/docs/facebook-login/permissions#reference-pages_read_engagement) or [pages\_show\_list](https://developers.facebook.com/docs/facebook-login/permissions#reference-pages_show_list) If the app user was granted a role on the Page via the Business Manager, you will also need one of: [ads\_management](https://developers.facebook.com/docs/permissions/reference/ads_management)[business\_management](https://developers.facebook.com/docs/permissions/reference/business_management) |

### **Time-based Pagination**

This endpoint supports [time-based pagination](https://developers.facebook.com/docs/graph-api/results#time). Include since and until query-string parameters with Unix timestamp or strtotime data values to define a time range.

### **Sample Request**

GET graph.facebook.com/v24.0/17841405822304914/media

### **Sample Response**

{  
  "data": \[  
    {  
      "id": "17895695668004550"  
    },  
    {  
      "id": "17899305451014820"  
    },  
    {  
      "id": "17896450804038745"  
    },  
    {  
      "id": "17881042411086627"  
    },  
    {  
      "id": "17869102915168123"  
    }  
  \]

}

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **IG User Media Publish**

Publish an [IG Container](https://developers.facebook.com/docs/instagram-api/reference/ig-container) on an Instagram Business [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user). Refer to the [Content Publishing](https://developers.facebook.com/docs/instagram-api/guides/content-publishing) guide for complete publishing steps.

## **Creating**

POST /{ig-user-id}/media\_publish

Publish an [IG Container](https://developers.facebook.com/docs/instagram-api/reference/ig-container) object on an Instagram Business [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user).

### **Limitations**

* An Instagram professional account can only publish 50 posts within a 24 hour moving period  
* If the [Page](https://developers.facebook.com/docs/instagram-api/overview#pages) connected to the targeted Instagram Business account requires [Page Publishing Authorization](https://www.facebook.com/business/m/one-sheeters/page-publishing-authorization) (PPA), PPA must be completed or the request will fail.  
* If the Page connected to the targeted Instagram Business account requires two-factor authentication, the Facebook User must also have performed two-factor authentication or the request will fail.

### **Requirements**

| Type | Description |
| ----- | ----- |
| [Access Tokens](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) | [User](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) |
| [Business Roles](https://www.facebook.com/business/help/442345745885606) | If publishing containers for [product tagging](https://developers.facebook.com/docs/instagram-api/guides/product-tagging), the app user must have an admin role on the [Business Manager](https://business.facebook.com/) that owns the IG User's [Instagram Shop](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F1187859655048322%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEe_V7WpAcH3wAU5V2loh9RzO6VHli3W73PDPyFyJTKQXI915rr87bhC1NRj-I_aem_0gWaOUFbU3J6uObHOM2n8Q&h=AT2Prg5-DQ9HwZ7QbDQdtDlGg-auHxXAtvCYMdNCNGXrCybLP3CP8RsH96mEtfH2OMg4akJjW5OAqnQBfOCff-5LSPfIQ_nHx6uQPZ0MuV6YrLyewgTPdGN_wutVSpP784YZBWNFmPs). |
| [Instagram Shop](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F1187859655048322%2F%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEebohKJOpXot5rwZBjCGOuy3ilffU1eX7e3dt-WhBaIkh_QX1GwZU18MFw-MA_aem_EbEzgKXPtONE7vuXH7PbWg&h=AT0O2XOJBmmRxxtXdFJIciNe5fJaYJGRzNwSkQx6MHmd9aIixQj4Q-VGp8kiybqCJy-Iie68mkyqFX7xpN3Mv7ARAcQCr6HPjrE1EZBL3jL4EP6MjiXLcKNnfdMK54EJcxiMqHlxjc4) | If publishing containers for [product tagging](https://developers.facebook.com/docs/instagram-api/guides/product-tagging), the IG User must have an approved [Instagram Shop](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F1187859655048322%2F%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEebohKJOpXot5rwZBjCGOuy3ilffU1eX7e3dt-WhBaIkh_QX1GwZU18MFw-MA_aem_EbEzgKXPtONE7vuXH7PbWg&h=AT0wRB0jNa6M0Ygb6l6yNMGrAcqBBtz10MTGB_kLuEHUaQC_CF5lwvxZRf3gVlJzPg_4JBZf_KXJp5AtaJCBsHQFYDP3YSpxh7q8mRimX9UMoZk_N-_1wFgqIadpq6eomE8HlDUbTjs) with a product catalog containing products. |
| [Permissions](https://developers.facebook.com/docs/apps/review/login-permissions) | [instagram\_basic](https://developers.facebook.com/docs/facebook-login/permissions#reference-instagram_basic)[instagram\_content\_publish](https://developers.facebook.com/docs/permissions/reference/instagram_content_publish) If the app user was granted a role on the Page via the Business Manager, you will also need one of: [ads\_management](https://developers.facebook.com/docs/permissions/reference/ads_management)ads\_read If publishing containers for [product tagging](https://developers.facebook.com/docs/instagram-api/guides/product-tagging), you will also need: [catalog\_management](https://developers.facebook.com/docs/permissions/reference/catalog_management)[instagram\_shopping\_tag\_products](https://developers.facebook.com/docs/permissions/reference/instagram_shopping_tag_products) |
| [Tasks](https://developers.facebook.com/docs/instagram-api/overview#tasks) | The app user whose token is used in the request must be able to perform MANAGE or CREATE\_CONTENT tasks on the [Page](https://developers.facebook.com/docs/instagram-api/overview#pages) connected to the targeted Instagram account. |

### **Request Syntax**

POST https://graph.facebook.com/{api-version}/{ig-user-id}/media\_publish  
  ?creation\_id={creation-id}

  \&access\_token={access-token}

### **Path Parameters**

| Placeholder | Value |
| ----- | ----- |
| {api-version}*String* | API [version](https://developers.facebook.com/docs/graph-api/guides/versioning). |
| {ig-user-id}Required*String* | App user's app-scoped user ID. |

### **Query String Parameters**

| Key | Placeholder | Description |
| ----- | ----- | ----- |
| access\_token Required | {access-token} | The app user's [User](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) access token. |
| creation\_id Required | {creation-id} | The ID of the [IG Container](https://developers.facebook.com/docs/instagram-api/reference/ig-container) to be published. |

### **Sample Request**

POST graph.facebook.com  
  /17841405822304914/media\_publish

    ?creation\_id=17889455560051444

### **Sample Response**

{  
  "id": "17920238422030506"

}

## **Reading**

This operation is not supported.

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **IG User Mentions**

This edge allows you to create an [IG Comment](https://developers.facebook.com/docs/instagram-api/reference/ig-comment) on an [IG Comment](https://developers.facebook.com/docs/instagram-api/reference/ig-comment) or captioned [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) object that an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has been @mentioned in by another Instagram user.

## **Creating**

### **Replying to a Captioned IG Media Object**

POST /{ig-user-id}/mentions?media\_id={media\_id}\&message={message}

Creates an [IG Comment](https://developers.facebook.com/docs/instagram-api/reference/ig-comment) on an [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) object in which an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has been @mentioned in a caption.

#### **Limitations**

* Mentions on Stories are not supported.  
* Commenting on photos in which you were tagged is not supported.  
* Webhooks will not be sent if the Media upon which the comment or @mention appears was created by an account that is set to private.

#### **Query String Parameters**

Query string parameters are optional unless indicated as required.

* {media\_id} (required) — the media ID contained in the [Webhook notification](https://developers.facebook.com/docs/instagram-api/guides/webhooks#reply-caption-mention) payload  
* {message} (required) — text to include in the commment

#### **Permissions**

A Facebook User [access token](https://developers.facebook.com/docs/instagram-api/overview#authentication) with the following permissions:

* instagram\_basic  
* instagram\_manage\_comments  
* pages\_read\_engagement

If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required:

* ads\_management  
* ads\_read

#### **Sample cURL Request**

curl \-i \-X POST \\  
 \-d "media\_id=17920112008063024" \\  
 \-d "message=Thanks%20for%20the%20dinosaur\!" \\  
 \-d "access\_token=a-valid-access-token-goes-here" \\

 "https://graph.facebook.com/17841405309211844/mentions"

#### **Sample Response**

{  
  "id": "17846319838228163"

}

### **Replying to a Comment**

POST /{ig-user-id}/mentions?media\_id={media\_id}\&comment\_id={comment\_id}\&message={message}

Creates an [IG Comment](https://developers.facebook.com/docs/instagram-api/reference/ig-comment) on an [IG Comment](https://developers.facebook.com/docs/instagram-api/reference/ig-comment) in which an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has been @mentioned.

#### **Limitations**

* Mentions on Stories are not supported.  
* Commenting on photos in which you were tagged is not supported.  
* Webhooks will not be sent if the Media upon which the comment or @mention appears was created by an account that is set to private.

#### **Query String Parameters**

Query string parameters are optional unless indicated as required.

* {comment\_id} (required) — the comment ID contained in the [Webhook notification](https://developers.facebook.com/docs/instagram-api/guides/webhooks#reply-comment-mention) payload  
* {media\_id} (required) — the media ID contained in the [Webhook notification](https://developers.facebook.com/docs/instagram-api/guides/webhooks#reply-caption-mention) payload  
* {message} (required) — text to include in the commment

#### **Permissions**

A Facebook User [access token](https://developers.facebook.com/docs/instagram-api/overview#authentication) with the following permissions:

* instagram\_basic  
* instagram\_manage\_comments  
* pages\_read\_engagement  
* pages\_show\_list

If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required:

* ads\_management  
* ads\_read

#### **Parameters**

* comment\_id (required)  
* media\_id (required)  
* message

#### **Sample cURL Request**

curl \-i \-X POST \\  
 \-d "media\_id=17920112008063024" \\  
 \-d "comment\_id=17918718562020960" \\  
 \-d "message=Hope%20you%20enjoy%20your%20new%20T-Rex\!" \\  
 \-d "access\_token=a-valid-access-token-goes-here" \\

 "https://graph.facebook.com/17841405309211844/mentions"

#### **Sample Response**

{  
  "id": "17846319838254687"

}

## **Reading**

This operation is not supported.

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **IG User Mentioned Comment**

Returns data on an [IG Comment](https://developers.facebook.com/docs/instagram-api/reference/ig-comment) in which an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has been @mentioned by another Instagram user.

## **Creating**

This operation is not supported.

## **Reading**

GET /{ig-user-id}?fields=mentioned\_comment.comment\_id

Returns data on an [IG Comment](https://developers.facebook.com/docs/instagram-api/reference/ig-comment) in which an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has been @mentioned by another Instagram user.

### **Limitations**

This endpoint will return an error if comments have been disabled on the [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) on which the [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has been @mentioned.

### **Requirements**

| Type | Description |
| ----- | ----- |
| [Access Tokens](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) | [User](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) |
| [Permissions](https://developers.facebook.com/docs/apps/review/login-permissions) | [instagram\_basic](https://developers.facebook.com/docs/permissions/reference/instagram_basic)[instagram\_manage\_comments](https://developers.facebook.com/docs/permissions/reference/instagram_manage_comments)[pages\_read\_engagement](https://developers.facebook.com/docs/permissions/reference/pages_read_engagement) If the app user was granted a role on the Page via the Business Manager, you will also need one of: [ads\_management](https://developers.facebook.com/docs/permissions/reference/ads_management)ads\_read |
| [Tasks](https://developers.facebook.com/docs/instagram-api/overview#tasks) | MANAGE, CREATE\_CONTENT, or MODERATE |

### **Request Syntax**

GET https://graph.facebook.com/v24.0/{ig-user-id}  
  ?fields=mentioned\_comment.comment\_id({comment-id}){{fields}}

  \&access\_token={access-token}

### **Query String Parameters**

| Parameter | Value |
| ----- | ----- |
| {access\_token}Required*String* | The app user's User Access Token. |
| {comment-id}Required*String* | The ID of the IG Comment in which the IG User has been @mentioned. The ID is included in the [Webhook notification](https://developers.facebook.com/docs/instagram-api/guides/webhooks#reply-comment-mention) payload. |
| {fields}*Comma-separated list* | A comma-separated list of IG Comment [Fields](https://developers.facebook.com/docs/instagram-platform/instagram-graph-api/reference/ig-user/mentioned_comment#fields) you want returned. If omitted, default fields will be returned. |

### **Fields**

| Field | Description |
| ----- | ----- |
| idDefault*String* | ID of the IG Comment. |
| like\_count*String* | Number of times the IG Comment has been liked. |
| media*String* | ID of the IG Media on which the IG Comment was made. Use [Field Expansion](https://developers.facebook.com/docs/instagram-platform/instagram-graph-api/reference/ig-user/mentioned_comment#field-expansion) to get additional fields on the returned IG Media entity. |
| textDefault*String* | Text of the IG Comment. |
| timestampDefault*String* | IG Comment creation date formatted in ISO 8601\. |

### **Response**

### **Sample Request**

curl \-X GET \\

  'https://graph.facebook.com/v24.0/17841405309211844?fields=mentioned\_comment.comment\_id(17873440459141021){timestamp,like\_count,text,id}\&access\_token=IGQVJ...'

#### **Sample Response**

{  
  "mentioned\_comment": {  
    "timestamp": "2017-05-03T16:09:08+0000",  
    "like\_count": 185,  
    "text": "Shout out to @metricsaurus",  
    "id": "17873440459141021"  
  },  
  "id": "17841405309211844"

}

### **Field Expansion**

You can expand the media field with a list of [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) fields to get additional data on the IG Media entity on which the comment was made. For example:

media{id,media\_url}

v10.0 and older calls until September 7, 2021: The [like\_count](https://developers.facebook.com/docs/instagram-api/reference/ig-media#fields) field on an IG Media will return 0 if the media [owner](https://developers.facebook.com/docs/instagram-api/overview#authorization) has [hidden](https://www.facebook.com/help/instagram/113355287252104) like counts on it.

v11.0+ calls, and all versions on September 7, 2021: If indirectly querying an [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) through another endpoint or field expansion, the [like\_count](https://developers.facebook.com/docs/instagram-api/reference/ig-media#fields) field will be omitted from API responses if the media owner has hidden like counts on it. Directly querying the IG Media (which can only be done by the IG Media owner) will return the actual like count, however, even if like counts have been hidden.

#### **Sample Field Expansion Request**

curl \-X GET \\

  'https://graph.facebook.com/v24.0/17841405309211844?fields=mentioned\_comment.comment\_id(17873440459141021){timestamp,like\_count,text,media{id,media\_url}}\&access\_token=IGQVJ...'

#### **Sample Field Expansion Response**

{  
  "mentioned\_comment": {  
    "timestamp": "2017-05-03T16:09:08+0000",  
    "like\_count": 185,  
    "text": "Shout out to @metricsaurus",  
    "id": "17873440459141021",  
    "media": {  
      "id": "17895695668004550",  
      "media\_url": "https://scont..."  
    }  
  },  
  "id": "17841405309211844"

}

### **Pagination**

If you are using field expansion to access an edge that supports [cursor-based pagination](https://developers.facebook.com/docs/graph-api/using-graph-api#paging), the response will include before and after cursors if the response contains multiple pages of data. Unlike standard cursor-based pagination, however, the response will not include previous or next fields, so you will have to use the before and after cursors to construct previous and next query strings manually in order to page through the returned data set.

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **Mentioned Media**

Returns data on an [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) in which an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has been @mentioned in a caption by another Instagram user.

## **Creating**

This operation is not supported.

## **Reading**

GET /{ig-user-id}?fields=mentioned\_media.media\_id

Returns data on an [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) in which an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has been @mentioned in a caption by another Instagram user.

### **Limitations**

* Mentions on Stories are not supported.  
* Commenting on photos in which you were tagged is not supported.  
* Webhooks will not be sent if the Media upon which the comment or @mention appears was created by an account that is set to private.

### **Requirements**

| Type | Description |
| ----- | ----- |
| [Access Tokens](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) | [User](https://developers.facebook.com/docs/facebook-login/access-tokens#usertokens) |
| [Permissions](https://developers.facebook.com/docs/apps/review/login-permissions) | [instagram\_basic](https://developers.facebook.com/docs/permissions/reference/instagram_basic)[instagram\_manage\_comments](https://developers.facebook.com/docs/permissions/reference/instagram_manage_comments)[pages\_read\_engagement](https://developers.facebook.com/docs/permissions/reference/pages_read_engagement)  If the app user was granted a role on the Page via the Business Manager, you will also need one of: [ads\_management](https://developers.facebook.com/docs/permissions/reference/ads_management)ads\_read |
| [Tasks](https://developers.facebook.com/docs/instagram-api/overview#tasks) | MANAGE, CREATE\_CONTENT, or MODERATE |

### **Request Syntax**

GET https://graph.facebook.com/v24.0/{ig-user-id}  
  ?fields=mentioned\_media.media\_id({media-id}){{fields}}

  \&access\_token={access-token}

### **Query String Parameters**

| Parameter | Value |
| ----- | ----- |
| {access\_token}Required*String* | The app user's User Access Token. |
| {fields}*Comma-separated list* | A comma-separated list of IG Media [Fields](https://developers.facebook.com/docs/instagram-platform/instagram-graph-api/reference/ig-user/mentioned_media#fields) you want returned. If omitted, default Fields will be returned. |
| {media-id}Required*String* | The ID of the IG Media in which the IG User has been @mentioned in a caption. The ID is included in the [Webhook notification](https://developers.facebook.com/docs/instagram-api/guides/webhooks#reply-comment-mention) payload. |

### **Fields**

| Field | Description |
| ----- | ----- |
| caption*String* | The caption text. Captions that @mention an IG User will not include the @ symbol unless the app user created the IG Media object upon which the caption was made. |
| comments*Object* | A list of IG Comments on the IG Media. If using Field Expansion to get the comment text, text that @mentions an IG User will not include the @ symbol unless the app user created the IG Media object upon which the caption was made. |
| comments\_count*String* | Number of IG Comments on the IG Media. |
| idDefault*String* | ID of the IG Media. |
| like\_count*String* | Count of likes on the media. Excludes likes on album child media and likes on promoted posts created from the media. Includes replies on comments. v10.0 and older calls: value will be 0 if the media owner has [hidden](https://www.facebook.com/help/instagram/113355287252104) like counts it. v11.0+ calls: field will be omitted if media owner has hidden like counts in it Value will be 0 if the media owner has [hidden](https://www.facebook.com/help/instagram/113355287252104) like counts it. |
| media\_type*String* | The IG Media's type: CAROUSEL\_ALBUM, IMAGE, STORY, or VIDEO. |
| media\_url*String* | URL of the published IG Media. |
| owner*String* | ID of the IG User who created the IG Media. Only returned if the app user created the IG Media object, otherwise the username field will be returned instead. |
| timestamp*String* | Creation date of IG Media formatted in ISO 8601\. |
| username*String* | Username of the IG User who created the IG Media. |

### **Sample Request**

curl \-X GET \\

  'https://graph.facebook.com/v24.0/17841405309211844?fields=mentioned\_media.media\_id(17873440459141021){caption,media\_type}\&access\_token=IGQVJ...'

### **Sample Response**

{  
  "mentioned\_media": {  
    "caption": "metricsaurus headquarters\!",  
    "media\_type": "IMAGE",  
    "id": "17873440459141021"  
  },  
  "id": "17841405309211844"

}

Note that in the sample above, the API has stripped out the leading @ symbol from the original caption (@metricsaurus headquarters\!) because the app user did not create the caption.

### **Pagination**

If you are using field expansion to access an edge that supports [cursor-based pagination](https://developers.facebook.com/docs/graph-api/using-graph-api#paging), the response will include before and after cursors if the response contains multiple pages of data. Unlike standard cursor-based pagination, however, the response will not include previous or next fields, so you will have to use the before and after cursors to construct previous and next query strings manually in order to page through the returned data set.

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **IG User Product Appeal**

Represents a rejected product's appeal status. See [Product Tagging](https://developers.facebook.com/docs/instagram-api/guides/product-tagging) guide for complete usage details.

## **Creating**

POST /{ig-user-id}/product\_appeal

Appeal a rejected product.

### **Limitations**

* Instagram Creator accounts are not supported.  
* Stories, Instagram TV, Reels, Live, and Mentions are not supported.

### **Requirements**

| Type | Requirement |
| ----- | ----- |
| Access Tokens | User |
| [Business Roles](https://www.facebook.com/business/help/442345745885606) | The app user must have an admin role on the [Business Manager](https://business.facebook.com/) that owns the IG User's [Instagram Shop](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F1187859655048322%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEeDkaRzQszRY716R_4_KKtU9QoXczKB0CmqWO9Gp5jGNjKP3lZwLF79Xmibwo_aem_ZtLs-DTv9Q4CA4r9VTlSwA&h=AT0p2FRYU74Ffghg3fo3spOegEHFlC-5-QmnYUQtvMAL6uYIctVkRE4pDIRDHa4botuuu1W5EtoxlpDUMu1s_VhY5VuBL6wpptqCawS2kgGsKwGw524p42phNOi0C9wh8BGacd2Mbfo). |
| [Instagram Shop](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F1187859655048322%2F%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEedPm2b0urVHkzfchW9LGEYwQWbB_0-DaQXdLgbuYFNsIqisxzadFnBuuxpeE_aem_BUsdE2aGKC4cExiQrQEakA&h=AT3eCAPpmzv3V6YSEI33JqNcgy7Tf8Ts-o-MbKpgMJ5KII_yrmDYkpjMCOzfsmc2dWBCJUayCUY3gTQmbhQqmVXALXdnohzagk9XOA2qbDcCz4VLF4h_TK_NtLcd8somAGUOnizZGVQ) | The IG User must have an approved [Instagram Shop](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F1187859655048322%2F%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEedPm2b0urVHkzfchW9LGEYwQWbB_0-DaQXdLgbuYFNsIqisxzadFnBuuxpeE_aem_BUsdE2aGKC4cExiQrQEakA&h=AT2EAW83ly6KhZ5soiC23BnbA8WAC0irW8O8KsfGaMPAbElT1wlz39W1UAhBtkOUfOK96x6nmIkltLsHTIiSVjOknYjpfmtuptXooi-Uhrbgnxg6WTyZr6xz06kguEaRfWSg49ZygHI) with a product catalog containing products. |
| [Permissions](https://developers.facebook.com/docs/permissions) | catalog\_management instagram\_basic instagram\_shopping\_tag\_products  If the app user was granted a role via the Business Manager on the Facebook Page connected to the targeted IG User, you will also need one of: ads\_management ads\_read |

### **Request Syntax**

POST https://graph.facebook.com/{api-version}/{ig-user-id}/product\_appeal  
  ?appeal\_reason={appeal-reason}  
  \&product\_id={product-id}

  \&access\_token={access-token}

### **Path Parameters**

| Placeholder | Value |
| ----- | ----- |
| {api-version} | API version |
| {ig-user-id} | Required. App user's app-scoped user ID. |

### **Query String Parameters**

| Key | Placeholder | Value |
| ----- | ----- | ----- |
| access\_token | {access-token} | Required. App user's User access token. |
| appeal\_reason | {appeal-reason} | Required. Explanation of why the product should be approved. |
| product\_id | {product-id} | Required. Product ID. |

### **Response**

An object indicating success or failure. Response does not indicate appeal outcome.

{  
  "success": {success}

}

#### **Response Contents**

| Property | Value |
| ----- | ----- |
| success | Indicates if API accepted request (true) or did not accept request (false). Response does not indicate appeal outcome. |

### **cURL Example**

#### **Request**

curl \-i \-X POST \\

 "https://graph.facebook.com/v24.0/90010177253934/product\_appeal?appeal\_reason=product%20is%20a%20toy%20and%20not%20a%20real%20weapon\&product\_id=4382881195057752\&access\_token=EAAOc..."

#### **Response**

{  
  "success": true

}

## **Reading**

GET /{ig-user-id}/product\_appeal

Get appeal status of a rejected product.

### **Limitations**

* Instagram Creator accounts are not supported.  
* Stories, Instagram TV, Reels, Live, and Mentions are not supported.

### **Requirements**

| Type | Requirement |
| ----- | ----- |
| Access tokens | User |
| [Business Roles](https://www.facebook.com/business/help/442345745885606) | The app user must have an admin role on the [Business Manager](https://business.facebook.com/) that owns the IG User's [Instagram Shop](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F1187859655048322%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEeDkaRzQszRY716R_4_KKtU9QoXczKB0CmqWO9Gp5jGNjKP3lZwLF79Xmibwo_aem_ZtLs-DTv9Q4CA4r9VTlSwA&h=AT1BmVpON3vz-oAeQ3nE1UcMn9ILfcVTMVcghbjZple6Zi4VZKqaoK3dOSdvGaj5Js84A8ydafpl4q9ruP5sU_7APDNdRJkU0BNJ698Ka6xe7EGQJHVRI_iwxcQevOm2K-PQMnoIrak). |
| [Instagram Shop](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F1187859655048322%2F%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEedPm2b0urVHkzfchW9LGEYwQWbB_0-DaQXdLgbuYFNsIqisxzadFnBuuxpeE_aem_BUsdE2aGKC4cExiQrQEakA&h=AT0P8HLknn3va4ZcuZQj_PUWjoEKu0IjwYlv20qfdYRPKLgdxCIdzVQSsgmpwKs2SCe8ODMKqq5QeKzx_ZL5qhyJ5T4MwaBGQpE8s1Xd7RgH_4B1Nv8BlXKnk_3JYWXSK84XjH3xgNc) | The IG User must have an approved [Instagram Shop](https://help.instagram.com/1187859655048322/?fbclid=IwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEedPm2b0urVHkzfchW9LGEYwQWbB_0-DaQXdLgbuYFNsIqisxzadFnBuuxpeE_aem_BUsdE2aGKC4cExiQrQEakA) with a product catalog containing products. |
| [Permissions](https://developers.facebook.com/docs/permissions) | catalog\_management instagram\_basic instagram\_shopping\_tag\_products  If the app user was granted a role via the Business Manager on the Facebook Page connected to the targeted IG User, you will also need one of: ads\_management ads\_read |

### **Request Syntax**

GET https://graph.facebook.com/{api-version}/{ig-user-id}/product\_appeal  
  ?product\_id={product-id}

  \&access\_token={access-token}

### **Path Parameters**

| Placeholder | Value |
| ----- | ----- |
| {api-version} | API version |
| {ig-user-id} | Required. App user's app-scoped user ID. |

### **Query String Parameters**

| Key | Placeholder | Value |
| ----- | ----- | ----- |
| access\_token | {access-token} | Required. App user's User access token. |
| product\_id | {product-id} | Required. Product ID. |

### **Response**

A JSON-formatted object containing an array of appeal status metadata.

{  
  "data": \[  
    {  
      "eligible\_for\_appeal": {eligible-for-appeal},  
      "product\_id": {product-id},  
      "review\_status": "{review-status}"  
    }  
  \]

}

#### **Response Contents**

| Property | Value |
| ----- | ----- |
| eligible\_for\_appeal | Indicates if decision can be appealed (yes if true, no if false). |
| product\_id | Product ID. |
| review\_status | Review status. Value can be: approved — Product is approved. rejected — Product was rejected pending — Still undergoing review. outdated — Product was approved but has been edited and requires reapproval. "" — No review status. no\_review — No review status. |

### **cURL Example**

#### **Request**

curl \-i \-X GET \\

 "https://graph.facebook.com/v24.0/90010177253934/product\_appeal?product\_id=4029274203846188\&access\_token=EAAOc..."

#### **Response**

{  
  "data": \[  
    {  
      "product\_id": 4029274203846188,  
      "review\_status": "approved",  
      "eligible\_for\_appeal": false  
    }  
  \]

}

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **Recently Searched Hashtags**

This edge allows you to determine the [IG Hashtags](https://developers.facebook.com/docs/instagram-api/reference/ig-hashtag) that an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has queried for within the last 7 days.

## **Reading**

GET /{ig-user-id}/recently\_searched\_hashtags

Get the hashtags that an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has queried using the [IG Hashtags](https://developers.facebook.com/docs/instagram-api/reference/ig-hashtag) endpoint within the last 7 days.

IG Users can query a maximum of 30 unique hashtags within a rolling, 7 day period. A queried hashtag will count against that user's limit as soon as it is queried. Subsequent queries on that hashtag within 7 days of the initial query will not count against the user's limit.

Limitations

* Emojis in hashtag queries are not supported.  
* The API returns 25 results per page by default, but you can use the limit parameter to get up to 30 per page (limit=30).

#### **Requirements**

| Type | Description |
| ----- | ----- |
| [Features](https://developers.facebook.com/docs/apps/review/feature) | [Instagram Public Content Access](https://developers.facebook.com/docs/apps/review/feature#reference-INSTAGRAM_PUBLIC_CONTENT_ACCESS) |
| [Permissions](https://developers.facebook.com/docs/apps/review/login-permissions) | [instagram\_basic](https://developers.facebook.com/docs/facebook-login/permissions#reference-instagram_basic)  If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required: ads\_management, ads\_read, or pages\_read\_engagement. |
| [Tokens](https://developers.facebook.com/docs/facebook-login/access-tokens) | A Facebook User [access token](https://developers.facebook.com/docs/instagram-api/overview#authentication). |

#### **Sample Request**

GET graph.facebook.com/17841405309211844/recently\_searched\_hashtags?limit=30

#### **Sample Response**

{  
  "data": \[  
    {  
      "id": "17841562906103814"  
    },  
    {  
      "id": "17841563587120501"  
    }  
  \]

}

## **Creating**

This operation is not supported.

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **Stories**

Represents a collection of story [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) objects on an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user).

## **Creating**

For creating Stories Media, refer to the [Instagram User Media](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media) documentation.

## **Reading**

### **Getting Stories**

GET /{ig-user-id}/stories

Returns a list of story [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) objects on an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user).

#### **Limitations**

* Responses will not include Live Video stories.  
* Stories are only available for 24 hours.  
* New stories created when a user reshares a story will not be returned.  
* Only one caption will be returned per Instagram story, even if more than one caption exists.

#### **Permissions**

A Facebook User [access token](https://developers.facebook.com/docs/instagram-api/overview#authentication) with the following permissions:

* instagram\_basic  
* pages\_read\_engagement

If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required:

* ads\_management  
* ads\_read

#### **Sample Request**

GET graph.facebook.com

  /17841405822304914/stories

#### **Sample Response**

{  
  "data": \[  
    {  
      "id": "17861937508009798"  
    },  
    {  
      "id": "17862253585030136"  
    },  
    {  
      "id": "17856428680064034"  
    },  
    {  
      "id": "17862537148046301"  
    },  
    {  
      "id": "17852121721080875"  
    },  
    {  
      "id": "17862694123018235"  
    }  
  \]

}

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **Tags**

Represents a collection of [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) objects in which your app user's [Instagram professional account](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has been tagged by another Instagram user.

## **Creating**

This operation is not supported.

## **Reading**

GET /\<IG\_USER\_ID\>/tags

Returns a list of [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) objects in which an [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) has been tagged by another Instagram user.

### **Limitations**

Private [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) objects will not be returned.

### **Requirements**

| Type | Description |
| ----- | ----- |
| Access Tokens | User |
| [Features](https://developers.facebook.com/docs/feature-reference) | Not applicable. |
| [Permissions](https://developers.facebook.com/docs/permissions#i) | instagram\_basic instagram\_manage\_comments pages\_read\_engagement  If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required: ads\_management or ads\_read. |
| [Tasks](https://developers.facebook.com/docs/instagram-platform/overview#tasks) | The app user must be able to perform appropriate Tasks on the Page based on the Permissions requested by the app. |

### **Request Syntax**

GET https://graph.facebook.com/\<IG\_USER\_ID\>/tags  
  ?fields=\<LIST\_OF\_FIELDS\>

  \&access\_token=\<ACCESS\_TOKEN\>

### **Query String Parameters**

Include the following query string parameters to augment the request.

| Key | Value |
| ----- | ----- |
| access\_tokenRequired*String* | The app user's Instagram User Access Token. |
| fields*Comma-separated list* | A comma-separated list of [Fields](https://developers.facebook.com/docs/instagram-platform/instagram-graph-api/reference/ig-user/tags#fields) and [Edges](https://developers.facebook.com/docs/instagram-platform/instagram-graph-api/reference/ig-user/tags#edges) you want included in the response. If omitted, default fields will be returned. |

### **Fields**

Use the fields query string parameter to specify fields you want included on any returned [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media#read) objects.

### **Edges**

Use the fields query string parameter to specify Edges you want included on any returned [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media#read) objects.

### **Response**

A JSON-formatted object containing [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) objects.

{  
  "\<FIELD\>":"\<VALUE\>",  
  ...

}

### **Pagination**

This edge supports [cursor-based pagination](https://developers.facebook.com/docs/graph-api/using-graph-api#paging) so the response will include before and after cursors if the response contains multiple pages of data. Unlike standard cursor-based pagination, however, the response will not include previous or next fields, so you will have to use the before and after cursors to construct previous and next query strings manually in order to page through the returned data set.

### **Sample Request**

GET graph.facebook.com/17841405822304914/tags  
    ?fields=id,username

    \&access\_token=EAADd...

### **Sample Response**

{  
  "data": \[  
    {  
      "id": "18038...",  
      "username": "keldo..."  
    },  
    {  
      "id": "17930...",  
      "username": "ashla..."  
    },  
    {  
      "id": "17931...",  
      "username": "jaypo..."  
    }  
  \]

}

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **Me**

This is a special endpoint that examines the Instagram User Access Token included in the request, determines the ID of the Instagram user who granted the token, and uses the ID to query the User endpoint.

## **Creating**

This operation is not supported.

## **Reading**

GET /me

Get fields and edges on the User whose Instagram User Access Token is being used in the query. This endpoint translates to GET /{user-id}, based on the User ID identified by the access token used in the query.

### **Request Syntax**

GET https://graph.instagram.com/v24.0/me  
  ?fields={fields}

  \&access\_token={access-token}

Refer to the User node reference for requirements and usage details.

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **Oauth Authorize**

This endpoint returns the Authorization Window, which app users use to authenticate their identity and grant your app permissions and Instagram User Access Tokens.

## **Creating**

This operation is not supported.

## **Reading**

GET /oauth/authorize

Get the Authorization Window.

### **Requirements**

None.

### **Request Syntax**

GET https://api.instagram.com/oauth/authorize  
  ?client\_id=\<APP\_ID\>,  
  \&redirect\_uri=\<REDIRECT\_URI\>,  
  \&response\_type=code,

  \&scope=\<PERMISSIONS\_APP\_NEEDS\>

### **Query String Parameters**

Augment the request with the following query parameters.

| Key | Sample Value | Description |
| ----- | ----- | ----- |
| client\_idRequired*Numeric string* | 990602627938098 | Your Instagram App ID displayed in the Meta App Dashboard |
| redirect\_uriRequired*String* | https://socialsizzle.herokuapp.com/auth/ | A URI where we will redirect users after they authenticate. Make sure this exactly matches one of the base URIs in your list of valid oAuth URIs. Keep in mind that the App Dashboard may have added a trailing slash to your URIs, so we recommend that you verify by checking the list. |
| response\_typeRequired*String* | code | Set this value to code. |
| scopeRequired*Comma-separated list* | instagram\_basic or instagram\_business\_basic | A comma-separated list, or URL-encoded space-separated list, of permissions to request from the app user. instagram\_basic or instagram\_business\_basic is required. |
| state*String* | 1 | An optional value indicating a server-specific state. For example, you can use this to protect against CSRF issues. We will include this parameter and value when redirecting the user back to you. |

### **Response**

The Authorization Window, which you should display to the app user. Once the user authenticates, the window will redirect to your redirect\_uri and include an Authentication Code, which you can then exchange for a short-lived Instagram User Access Token.

Note that we \#\_ append to the end of the redirect URI, but it is not part of the code itself, so strip it out before exchanging it for a short-lived token.

### **HTTP Example**

https://api.instagram.com/oauth/authorize  
  ?client\_id=990602627938098  
  \&redirect\_uri=https://socialsizzle.herokuapp.com/auth/  
  \&scope=instagram\_business\_basic

  \&response\_type=code

### **Successful Authorization**

If authentication is successful, the Authorization Window will redirect the user to your redirect\_uri and include an Authorization Code. Capture the code so you can exchange it for a short-lived access token.

Codes are valid for 1 hour and can only be used once.

#### **Sample Successful Authorization Redirect**

https://socialsizzle.herokuapp.com/auth?code=AQBx-hBsH3...

### **Canceled Authorization**

If the user cancels the authorization flow, we will redirect the user to your redirect\_uri and append the following error parameters. *It is your responsibility to fail gracefully in these situations and display an appropriate message to your users*.

| Parameter | Value |
| ----- | ----- |
| error | access\_denied |
| error\_reason | user\_denied |
| error\_description | The+user+denied+your+request |

#### **Sample Canceled Authentication Redirect**

https://socialsizzle.herokuapp.com/auth/  
  ?error=access\_denied  
  \&error\_reason=user\_denied

  \&error\_description=The+user+denied+your+request

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **Page**

Represents a Facebook Page.

This node allows you to:

* get the [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) connected to a Facebook Page.

Available via Facebook Login for Business only.

## **Creating**

This operation is not supported.

## **Reading**

### **Getting a Page's IG User**

GET /\<PAGE\_ID\>?fields=instagram\_business\_account

Returns the [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) connected to the Facebook Page.

#### **Permissions**

A Facebook User [access token](https://developers.facebook.com/docs/instagram-api/overview#authentication) with the following permissions:

* instagram\_basic  
* pages\_show\_list

If the token is from a User whose Page role was granted via the Business Manager, one of the following permissions is also required:

* ads\_management  
* ads\_read

#### **Sample Request**

GET graph.facebook.com

  /134895793791914?fields=instagram\_business\_account

#### **Sample Response**

{  
  "instagram\_business\_account": {  
    "id": "17841405822304914"  
  },  
  "id": "134895793791914"

}

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **Refresh Access Token**

This endpoint allows you to refresh long-lived Instagram User Access Tokens.

## **Creating**

This operation is not supported.

## **Reading**

GET /refresh\_access\_token

Refresh a long-lived accesstoken that is at least 24 hours old but has not expired. Refreshed tokens are valid for 60 days from the date at which they are refreshed.

### **Requirements**

| Type | Requirement |
| ----- | ----- |
| Access tokens | Instagram User (long-lived) |
| Permissions | instagram\_business\_basic |

### **Request Syntax**

GET https://graph.instagram.com/refresh\_access\_token  
  ?grant\_type=ig\_refresh\_token

  \&access\_token=\<LONG\_LIVED\_ACCESS\_TOKENS\>

### **Query String Parameters**

Include the following query string parameters to augment the request.

| Key | Value |
| ----- | ----- |
| grant\_typeRequired*String* | Set this to ig\_refresh\_token. |
| access\_tokenRequired*String* | The valid (unexpired) long-lived Instagram User Access Token that you want to refresh. |

### **Response**

A JSON-formatted object containing the following properties and values.

{  
  "access\_token": "\<ACCESS\_TOKEN\>",  
  "token\_type": "\<TOKEN\_TYPE\>",  
  "expires\_in": \<EXPIRES\_IN\>

}

Response Contents

| Value Placeholder | Value |
| ----- | ----- |
| \<ACCESS\_TOKEN\>*Numeric string* | A long-lived Instagram User Access Token. |
| \<TOKEN\_TYPE\>*String* | bearer |
| \<EXPIRES\_IN\>*Integer* | The number of seconds until the long-lived token expires. |

### **cURL Example**

#### **Request**

curl \-X GET \\

  'https://graph.instagram.com/refresh\_access\_token?grant\_type=ig\_refresh\_token\&access\_token=F4RVB...'

#### **Response**

{  
  "access\_token": "c3oxd...",  
  "token\_type": "bearer",  
  "expires\_in": 5183944

}

## **Updating**

This operation is not supported.

## **Deleting**

This operation is not supported.

# **Facebook Login for Business**

Facebook Login for Business is a custom, [manual Facebook Login flow](https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow) that makes it easier for you to onboard Instagram users who still need to configure their account for API access.

In order to make their account accessible to our APIs, Instagram users must first convert their account to a Professional account, create a Facebook Page that represents their business, then connect that Page to their account.

Facebook Login for Business simplifies this process by allowing Instagram users to complete all of these steps in a single window instead of having to complete them in the Facebook and Instagram apps.

## **Before You Start**

You will need a Meta [Business type app](https://developers.facebook.com/docs/development/create-an-app/other-app-types) to add the following products:

* Instagram \> API setup with Facebook login  
* Facebook Login for Business  
* Webhooks

Add the Facebook Login for B product to your app and add a redirect URL. We will redirect users to this URL after they complete the onboarding flow.

1. Go to the [Apps Panel](https://developers.facebook.com/apps) and select your app to load it in the App Dashboard.  
2. In the left-hand menu, click Add Products, locate Facebook Login for Business, then click Set Up to add it to your app.  
3. In the left-hand menu under Facebook Login for Business, click Settings.  
4. In the Client OAuth Settings \> Valid OAuth Redirect URIs field, enter your redirect URL.

## **Step 1: Construct the Login URL**

Construct the Business Login URL using its base URL and query string parameters.

### **URL Syntax**

https://www.facebook.com/dialog/oauth  
  ?client\_id={client-id}  
  \&display={display}  
  \&extras={extras}  
  \&redirect\_uri={redirect-uri}  
  \&response\_type={response\_type}

  \&scope={scope}

### **Query String Parameters**

All parameters are required.

| Key | Placeholder | Description | Sample Value |
| ----- | ----- | ----- | ----- |
| client\_id | {client-id} | Your Meta app ID. | 442224939723604 |
| display | {display} | Set to page. | page |
| extras | {extras} | Set to {"setup":{"channel":"IG\_API\_ONBOARDING"}}. | {"setup":{"channel":"IG\_API\_ONBOARDING"}} |
| redirect\_uri | {redirect\_uri} | URL to redirect user to after completing login flow. This URL must match a URL in the Facebook Login \> Settings \> Client OAuth Settings \> Valid OAuth Redirect URI field in the App Dashboard. See [Before you start](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/business-login-for-instagram#before-you-start). | https://my-clever-redirect-url.com/success/ |
| response\_type | {response\_type} | Set to token. | token |
| scope | {scope} | A list of permissions you want to request from the user. The list of permission you request will depend on which APIs your app relies on. Refer the endpoint references for any endpoints your app uses to determine which permission you should request. | instagram\_basic,instagram\_content\_publish,instagram\_manage\_comments,instagram\_manage\_insights,pages\_show\_list,pages\_read\_engagement |

### **Example URL**

This is an example of a URL constructed by an app that relies on the Instagram Messaging API and has therefore requested permissions required by Messaging API endpoints.

https://www.facebook.com/v24.0/dialog/oauth?client\_id=442224939723604\&display=page\&extras={"setup":{"channel":"IG\_API\_ONBOARDING"}}\&redirect\_uri=https://my-clever-redirect-url.com/success/\&response\_type=token\&scope=instagram\_basic,instagram\_content\_publish,instagram\_manage\_comments,instagram\_manage\_insights,pages\_show\_list,pages\_read\_engagement

## **Step 2: Assign the URL to a Button**

Assign the URL to a standard anchor link or button of your own design and display it to any users who you are certain have not completed the onboarding flow.

### **Example Anchor Link**

\<a href\="https://www.facebook.com/v24.0/dialog/oauth?client\_id=442224939723604\&display=page\&extras={"setup":{"channel":"IG\_API\_ONBOARDING"}}\&redirect\_uri=https://my-clever-redirect-url.com/success/\&response\_type=token\&scope=instagram\_basic,instagram\_content\_publish,instagram\_manage\_comments,instagram\_manage\_insights,pages\_show\_list,pages\_read\_engagement"\>Login to Facebook\</a\>

## **Step 3: Capture User access token**

After a user clicks your link or button and completes the Business Login for Instagram flow, we will redirect the user to the URL you assigned to the redirect\_uri. We will also append a URL fragment (\#) with the user's short-lived [User access token](https://developers.facebook.com/docs/facebook-login/guides/access-tokens#usertokens), some metadata about the token, and the user's [long-lived access token](https://developers.facebook.com/docs/facebook-login/guides/access-tokens/get-long-lived). Capture the long-lived access token.

### **Canceled Login**

If a user cancels out of the login flow we will still redirect the user to your redirect\_uri but append different data. Refer to the [Manually Build a Login Flow](https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow) document's [Canceled Login](https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow#nonjscancel) section to learn how process these redirects.

### **Redirect Syntax**

{redirect-url}?  
  \#access\_token={access-token}  
  \&data\_access\_expiration\_time={data-access-expiration-time}  
  \&expires\_in={expires-in}

  \&long\_lived\_token={long\-lived-token}

### **Fragment Providers**

Token values have been truncated (...) in this example for readibility.

| Key | Placeholder | Description | Sample Value |
| ----- | ----- | ----- | ----- |
| access\_token | {access-token} | The user's short-lived [User access token](https://developers.facebook.com/docs/facebook-login/guides/access-tokens#usertokens). | EAAHm... |
| data\_access\_expiration\_time | {data-access-expiration-time} | ISO 8601 timestamp when [data access expires](https://developers.facebook.com/docs/facebook-login/auth-vs-data/#data-access-expiration). | 1658889585 |
| expires\_in | {expires-in} | Number of seconds until the short-lived User access token expires. | 4815 |
| long\_lived\_token | {long-lived-token} | The user's [long-lived access token](https://developers.facebook.com/docs/facebook-login/guides/access-tokens/get-long-lived). | ABAEs... |

### **Example Redirect**

Token values have been truncated (...) in this example for readability.

https://my-clever-redirect-url.com/success/?\#access\_token=EAAHm...\&data\_access\_expiration\_time=1658889585\&expires\_in=4815\&long\_lived\_token=ABAEs...

## **Step 4: Get the User's Page, Page Access Token, and Instagram Business Account**

Send a request to the GET /me/accounts endpoint and request the following fields:

* id  
* name  
* access\_token  
* instagram\_business\_account

This will return a collection of Facebook Pages that the user can perform tasks on. For each Page in the result set, the response will include its:

* ID  
* Name  
* [Page access token](https://developers.facebook.com/docs/facebook-login/guides/access-tokens#pagetokens)  
* Instagram Business account ID connected to the Page

### **cURL Example**

Token values have been truncated (...) in this example for readability.

curl \-i \-X GET \\

 "https://graph.facebook.com/v24.0/me/accounts?fields=id%2Cname%2Caccess\_token%2Cinstagram\_business\_account\&access\_token=EAACw..."

### **Example Response**

Token values have been truncated (...) in this example for readability.

{  
  "data": \[  
    {  
      "id": "134895793791914",  
      "name": "Metricsaurus",  
      "access\_token": "EAACw...",  
      "instagram\_business\_account": {  
        "id": "17841405309211844"  
      }  
    }  
  \],  
  "paging": {  
    "cursors": {  
      "before": "MTc1NTg0Nzc2ODAzNDQwMgZDZD",  
      "after": "MTc1NTg0Nzc2ODAzNDQwMgZDZD"  
    }  
  }

}

Capture the Page ID (id), Page access token (access\_token), and Instagram Professional account ID (instagram\_business\_account) for the Page that the user has connected to their Instagram Professional account.

Keep in mind that some users may be able to perform tasks on more than one Page. If multiple Pages are included in the response, you may have to surface each Page's name (name) to the user so they can identify which Page's data to capture.

## **Conclusion**

You should now have everything you need to help your access and work with their data using our various APIs:

* Instagram Business account ID  
* ID of the Facebook Page connected to the Instagram Business account  
* the Facebook Page's access token (required by the [Instagram Messaging API](https://developers.facebook.com/docs/messenger-platform/instagram))  
* a User access token from the user, who is able to perform tasks on the Facebook Page connected to the Instagram Business account (required by the [Instagram Graph API](https://developers.facebook.com/docs/instagram-api))

# **Business Discovery**

You can use the Instagram API with Facebook Login to get basic metadata and metrics about other Instagram professional accounts.

### **Limitations**

Data about age-gated Instagram professional accounts will not be returned.

### **Endpoints**

The API consists of the following endpoints. Refer to the endpoint's reference documentation for parameter and permission requirements.

* [GET /\<YOUR\_APP\_USERS\_IG\_USER\_ID\>/business\_discovery](https://developers.facebook.com/docs/instagram-api/reference/ig-user/business_discovery)

## **Examples**

### **Get Follower & Media Count**

This sample query shows how to get the number of followers and number of published media objects on the [Blue Bottle Coffee](https://l.facebook.com/l.php?u=https%3A%2F%2Fwww.instagram.com%2Fbluebottle%2F%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEeQlNpFNDnBri-Gsw71ClX9a5mZBp3QdSe46o31n6C_M0n3sSlJpaWUxG2FSQ_aem__fe5R7OHWlTuqcr7RKX7EA&h=AT2WHrvZ5JEh5OAZGp5Md2MSWzPk6kXuk1jqNaOh3hLDzOWhIPuwEFYua9Xb7J1RPVMxNy9XIisQsiadUL8xabUnVhpE_g7aCbtaU8y-PTbfJSETok6_UdNXnQ0kJmJB-iVv8wMpvRE) Instagram professional account. Notice that business discovery queries are performed on the app user's Instagram professional account ID (in this case, 17841405309211844) with the username of the Instagram professional account that your app user is attempting to get data about (bluebottle in this example).

#### **Sample Request**

*Formatted for readability.*

curl \-i \-X GET \\  
 "https://graph.facebook.com/v24.0/17841405309211844 \\  
  ?fields=business\_discovery.username(bluebottle){followers\_count,media\_count} \\

  \&access\_token=\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ACCESS\_TOKEN\>"

#### **Sample Response**

{  
  "business\_discovery": {  
    "followers\_count": 267793,  
    "media\_count": 1205,  
    "id": "17841401441775531" // Blue Bottle's Instagram user ID  
  },  
  "id": "17841405309211844"  // Your app user's Instagram user ID

}

### **Get Media**

Since you can make nested requests by specifying an edge via the fields parameter, you can request the targeted professional account's media edge to get all of its published media objects.

#### **Sample Request**

*Formatted for readability.*

curl \-i \-X GET \\  
 "https://graph.facebook.com/v24.0/17841405309211844 \\  
  ?fields=business\_discovery.username(bluebottle){followers\_count,media\_count,media} \\

  \&access\_token=\<YOUR\_APP\_USERS\_INSTAGRAM\_USER\_ACCESS\_TOKEN\>"

#### **Sample Response**

{  
  "business\_discovery": {  
    "followers\_count": 267793,  
    "media\_count": 1205,  
    "media": {  
      "data": \[  
        {  
          "id": "17858843269216389"  
        },  
        {  
          "id": "17894036119131554"  
        },  
        {  
          "id": "17894449363137701"  
        },  
        {  
          "id": "17844278716241265"  
        },  
        ... // results truncated for brevity  
      \],  
    "id": "17841401441775531"  
  },  
  },  
  "id": "17841405309211844"

}

### **Get Basic Metrics on Media**

You can use both nested requests and field expansion to get public fields for a Business or Creator Account's media objects. Note that this does not grant you permission to access media objects directly — performing a GET on any returned [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media) will fail due to insufficient permissions.

For example, here's how to get the number of comments and likes for each of the media objects published by Blue Bottle Coffee:

Please note that view\_count includes both paid and organic metrics

### **Sample Request**

GET graph.facebook.com  
  /17841405309211844

    ?fields=business\_discovery.username(bluebottle){media{comments\_count,like\_count,view\_count}}

### **Sample Response**

{  
  "business\_discovery": {  
    "media": {  
      "data": \[  
        {  
          "comments\_count": 50,  
          "like\_count": 5837,  
          "view\_count": 7757,  
          "id": "17858843269216389"  
        },  
        {  
          "comments\_count": 11,  
          "like\_count": 2997,  
          "id": "17894036119131554"  
        },  
        {  
          "comments\_count": 28,  
          "like\_count": 3643,  
          "id": "17894449363137701"  
        },  
        {  
          "comments\_count": 43,  
          "like\_count": 4943,  
          "id": "17844278716241265"  
        },  
     \],  
   },  
   "id": "17841401441775531"  
  },  
  "id": "17841405976406927"

}

# **Instagram Creator Marketplace API**

Instagram's creator marketplace is where brands can discover and evaluate Instagram creators for [partnership ads](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F292748974937716%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEe1XDflwpxuhAOBzuGEsYFfbNtRmdQvODwDG1y1gZU7cT-_VJO6KU8vhq3B7E_aem_AdbDU8PNT837RDjHI9KLKQ&h=AT1K1rihPhmOGK9SMfeJMX5MAnWRqRfh2IWRjGdtP5BPNE5APcyzInP7Pz0LI7LBAQDTJzJ71aP1CIFGZhmfANT3nYo2bdwa8ZrVWJwjXhXVoj5jaFsvZ8v0PCHc-WFyrkVoW8Rtlmk). The API offers personalized creator recommendations and search using authenticated first-party data to help brands find the right creators for their partnership ad campaigns. Brands can evaluate creators for partnership ads using authenticated, real-time 1st party data.

Notice: New submissions to app review for the app permission instagram\_creator\_marketplace\_discovery are currently paused.

## **Before You Start**

In order to use these APIs, you must ask the brand to grant you permissions using [Facebook Login](https://developers.facebook.com/docs/facebook-login/). You will need these permissions to access the creator marketplace API:

* instagram\_creator\_marketplace\_discovery  
* instagram\_basic  
* pages\_manage\_metadata  
* pages\_show\_list  
* business\_management

Note: For the instagram\_creator\_marketplace\_discovery permission, your app must have advanced access.

In the brand onboarding flow, Meta will check if the brand is [eligible for Instagram’s creator marketplace](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F672550269221197%2F%3Fhelpref%3Drelated_articles%26fbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEe1st68SLOlcM7Up8p78yL8cSHJmAliGh0AWTT4LihQakobOBjzDXT47650A8_aem_PTylg09lNxl_0pKmTjMkew&h=AT3nqvwsi8KY4B1y7v6D-ZXINPbrkXa0CYa_IxXrNubU2VBhflLRNK2DQlXbS1usDkggMaOf_aHYT0zdc0SuQ3IKjo5WRH0tcxxwQJdVr-J4akvpOCV5yrQvjCSD6OyULlU68K_1AyA). If the brand is eligible but has not onboarded to the creator marketplace, they will need to accept the [Instagram Creator Marketplace Terms of Service](https://www.facebook.com/business/help/488723392994445).

## **Tokens**

Creator marketplace APIs rely on you passing in a [Page Access Token](https://developers.facebook.com/docs/facebook-login/guides/access-tokens/#pagetokens), where the page is connected to the brand’s Instagram Business Account and is eligible to onboard or has onboarded to Instagram’s creator marketplace as a brand.

## **Login Flow Experience**

Play

\-0:17

Mute

Additional Visual Settings

Enter Fullscreen

![][image1]

### **Rate Limits**

The creator marketplace APIs enforce rate limits at both the Instagram account level and the application level.

* Account-level limits: Requests are capped at 240 per user per hour  
* Application-level limits: The number of calls an app can make within a rolling one-hour window is calculated as: Calls per hour \= 200 \* Number of Effective Users. Number of Effective Users is determined by the app's number of unique daily active users (DAUs). If your app experiences fluctuating usage, such as higher activity on weekends and lower activity during weekdays, the calculation may use weekly or monthly active users to better reflect typical usage patterns instead.

## **Discovery API**

The Discovery API offers personalized creator recommendations to the authenticated brand, leveraging existing data from all over Instagram, allowing the brand to discover new, relevant creators, personalized to their brand and campaign needs.

Instagram’s creator marketplace prioritizes creators who will perform well in the brand's partnership ads campaigns.

### **API Permission Requirements**

* business\_management  
* instagram\_basic  
* instagram\_creator\_marketplace\_discovery  
* pages\_manage\_metadata  
* pages\_show\_list

### **Sample Request**

GET /{ig-user-id}/creator\_marketplace\_creators

### **API Parameters**

* When a creator username is specified, other filter parameters (e.g., creator\_countries) cannot be applied.  
* When similar\_to\_creators is used, keyword search (query) cannot be used.  
* query can be combined with other filters (e.g., creator\_age\_bucket).  
* If the searched username (username) matches the username of an eligible professional account, that account will be returned regardless of its creator marketplace onboarding status.

| Parameter | Description |
| ----- | ----- |
| creator\_countries | Filter creators based on their country. Input will be the country ISO code. For example: creator\_countries=\['US'\] |
| creator\_min\_followers | Minimum follower count for creators. Supported values are 0, 10000, 25000, 50000, 75000, 100000. |
| creator\_max\_followers | Maximum follower count for creators. Supported values are 10000, 25000, 50000, 75000, 100000. |
| creator\_age\_bucket | Filter creators based on their age range. Supported values are 18\_to\_24, 25\_to\_34, 35\_to\_44, 45\_to\_54, 55\_to\_64, 65\_and\_above. |
| creator\_interests | Filter creators based on category list input. Supported values are ANIMALS\_AND\_PETS, BOOKS\_AND\_LITERATURE, BUSINESS\_FINANCE\_AND\_ECONOMICS, EDUCATION\_AND\_LEARNING, BEAUTY, FASHION, FITNESS\_AND\_WORKOUTS, FOOD\_AND\_DRINK, GAMES\_PUZZLES\_AND\_PLAY, HISTORY\_AND\_PHILOSOPHY, HOLIDAYS\_AND\_CELEBRATIONS, HOME\_AND\_GARDEN, MUSIC\_AND\_AUDIO, PERFORMING\_ARTS, SCIENCE\_AND\_TECH, SPORTS, TV\_AND\_MOVIES, TRAVEL\_AND\_LEISURE\_ACTIVITIES, VEHICLES\_AND\_TRANSPORTATION, VISUAL\_ARTS\_ARCHITECTURE\_AND\_CRAFTS |
| creator\_gender | Filter creators based on their gender. Supported values are male, female. |
| creator\_min\_engaged\_accounts | Minimum engagement metric of the audience for a creator's content. Supported values are 0, 2000, 10000, 50000, 100000. |
| creator\_max\_engaged\_accounts | Maximum engagement metric of the audience for a creator's content. Supported values are 2000, 10000, 50000, 100000 |
| major\_audience\_age\_bucket | Filter creators based on their audience's age group. Supported values are 18\_to\_24, 25\_to\_34, 35\_to\_44, 45\_to\_54, 55\_to\_64, 65\_and\_above |
| major\_audience\_gender | Filter creators based on their audience's gender. Supported values are male, female |
| major\_audience\_countries | Filter creators based on the location of their audience. Input will be the country ISO code list. |
| query | A free-text search to find creators based on a list of keywords (e.g., username or content-related terms). For example: to find travel-related accounts, use query=travel. |
| similar\_to\_creators | A list of creators similar to the specified creator. Limited to only onboarded creators, input can be a list of usernames and the maximum username input is 5\. |
| username | Creator instagram username |
| reels\_interaction\_rate | The percentage of views that liked, commented, shared and saved this creator’s recent reels. This is calculated as the number of views that engaged with the reels divided by the total number of initial views. |

### **Response Fields**

| Field | Description |
| ----- | ----- |
| username | The Instagram handle or username of the creator. |
| is\_account\_verified | Indicates whether the creator’s Instagram account is verified. |
| biography | The bio or description provided by the creator on their Instagram profile. |
| country | The country in which the creator is based. |
| gender | The gender of the creator, only available for onboarded creators). |
| age\_bucket | The age range or bucket to which the creator belongs, only available for onboarded creators. 18-24,25-34. |
| insights | Metric insights of a creator, such as account\_engaged\_count:30 |
| onboarded\_status | Whether a creator has been onboarded to the creator marketplace. |
| id | Instagram ID |
| email | Creator’s email, if available |
| portfolio\_url | Creator’s portfolio url, if available |
| has\_brand\_partnership\_experience | Whether the creator has branded content or partnership ads collaboration experience in the past year. For this field to return values, username has to be specified in the API call. |
| past\_brand\_partnership\_partners | The brands the creator has collaborated with on branded content or partnership ads in the past year. For this field to return values, username has to be specified in the API call. |
| branded\_content\_media | Returns the 30 most recent branded content organic media of a particular creator. See insights section for supported fields. For this field to return values, username has to be specified in the API call. |
| recent\_media | Returns the top 30 recent media of a particular creator. See insights section for supported fields. For this field to return values, username has to be specified in the API call. |

#### **Example API Call**

GET graph.facebook.com/{ig-user-id}/creator\_marketplace\_creators?creator\_countries=\['US'\]\&fields=id, username, country, gender

#### **Example API Response**

"data": \[  
    {  
      "id": "178414869381437360",  
      "username": "xxx",  
      "country": "BR",  
      "gender": "female"  
    },  
    {  
      "id": "178414965628223823",  
      "username": "xxx",  
      "country": "IN",  
      "gender": "male"  
    },

\]

### **Error Codes**

| Error Code | Description |
| ----- | ----- |
| 10 Permission Denied App Missing Permission | The app lacks the required instagram\_creator\_marketplace\_discovery permission. |
| 10 Permission Denied | Brand Eligibility The brand is not eligible to onboard to the creator marketplace, so the API call cannot be made. |
| 100 Invalid Parameter Input | General message for invalid API parameter inputs (e.g., account follower count minimum bound exceeds the upper bound, category exceeds the limit of 5). |

## **Creator Insights API**

### **API Permission Requirements**

* business\_management  
* instagram\_basic  
* instagram\_creator\_marketplace\_discovery  
* pages\_manages\_metadata  
* pages\_show\_list

### **Creator Insights Metrics**

| Metric | Supported Periods | Supported Time Range | Supported Granularity |
| ----- | ----- | ----- | ----- |
| total\_followers | Overall | Lifetime | NA |
| creator\_engaged\_accounts | Day, Overall | this\_week, last\_14\_days, this\_month | follow\_type, gender, age, top\_countries, top\_cities |
| creator\_reach | Day, Overall | this\_week, last\_14\_days, this\_month | follow\_type, media\_type |
| reels\_interaction\_rate | Overall | last\_90\_days | NA |
| reels\_hook\_rate | Overall | last\_90\_days | NA |

### **Sample API Call**

GET {ig\_user\_id}/creator\_marketplace\_creators?username={creator\_username}\&fields=insights.metrics(creator\_reach).breakdown(follow\_type)

### **Media Insights Metrics**

| Field | Description |
| ----- | ----- |
| id | Media id of the content eg 17841463918802342 |
| product\_type | The media product type eg Reels |
| media\_type | The media type eg Image |
| permalink | The permalink of the social media eg https://www.instagram.com/reel/C\_Tlbb-sGMi/ |
| creation\_time | The creation time that the media was created eg 2024-08-30T19:36:26+0000 |
| caption | The caption of the media eg Test Caption |
| tagged\_brand | The brand that the organic media has been paid to partner with. If the media is not a branded content with partnership, the field will not be returned. eg instagram |
| likes | Total number of likes the media receives. eg 1000 |
| comments | Total number of comments the media receives. eg 500 |
| views | Total number of views on the media (only available for video media). eg 30000 |
| shares | Total number of shares the media gets. eg 800 |

### **Sample API Call**

GET {ig\_user\_id}/creator\_marketplace\_creators?username={creator\_username}\&fields=branded\_content\_media{media\_type,insights.metrics(views)},recent\_media{media\_type,insights.metrics(views)}

### **Error Codes**

| Error Code | Description |
| ----- | ----- |
| 100 Invalid Parameter Input | General message for invalid API parameter inputs (e.g., the input period is not supported by the provided metrics). |

# **Copyright Detection**

This guide shows you how to detect copyright violations for a video uploaded or published to Instagram using the Instagram Graph API.

We only support Instagram media created via the content publishing API for early copyright detection.

## **Before you start**

Before you start you will need the following:

* All requirements and limitations for accessing the Instagram Container and Instagram Media endpoints apply

### **Best practices**

When testing an API call, you can include the access\_token parameter set to your access token. However, when making secure calls from your app, use the [access token class.](https://developers.facebook.com/docs/facebook-login/guides/access-tokens#portabletokens)

## **Check an uploaded video**

To check the copyright status for a video that have been uploaded, but not yet published, send a GET request to the /{ig-containter-id} endpoint with the fields parameter set to copyright\_check\_status.

### **Sample Request**

curl \-i \-X GET "https://graph.facebook.com/v24.0/{ig-containter-id}?fields=copyright\_check\_status"

   

On success, your app receives a JSON response with a copyright\_check\_status object with the following key-value pairs:

* status set to completed, error, in\_progress, or not\_started  
* matches\_found set to:  
  * false if none are detected  
  * true if violations are detected and author, content\_title, matched\_segments, and owner\_copyright\_policy values

### **Sample Responses**

| Violation found {   "copyright\_check\_status": {     "status": "complete",     "matches\_found": true   },   "id": "{ig-containter-id}" } | No violation found {   "copyright\_check\_status": {       "status": "in\_progress",       "matches\_found": false   } } |
| :---- | :---- |

## **Check a published video**

To check the copyright status for a video that has been published, send a GET request to the /{ig-media-id} endpoint with the fields parameter set to copyright\_check\_information.

### **Sample Request**

curl \-i \-X GET "https://graph.facebook.com/v24.0/{ig-media-id}?fields=copyright\_check\_information"

   

On success, your app receives a JSON response with the id set to the video being checked and the copyright\_check\_information object with the following:

* status set to a status object set to completed, error, in\_progress, or not\_started  
* copyright\_matches set to:  
  * false – Returned when no copyright violations are detected  
  * true – Returned when copyright violations are detected and includes the copyright\_check\_information object that contains information about the copyright owner, policy, mitigation steps, and sections of the media that violated the copyright.

### **Sample Responses**

| Violation found {   "copyright\_check\_information": {      "status": {        "status": "complete",        "matches\_found": true      },      "copyright\_matches": \[        {          "content\_title": "In My Feelings",          "author": "Drake",          "owner\_copyright\_policy": {            "name": "UMG",            "actions": \[              {                "action": "BLOCK",                "territories": "3",                "geos": \[                  "Canada",                  "India",                  "United States of America"                \]              },              {                "action": "MUTE",                "territories": "4",                "geos": \[                  "Taiwan",                  "Tanzania",                  "Saudi Arabia",                  "United Kingdom of Great Britain and Northern Ireland"                \]              }            \]          },          "matched\_segments": \[           {             "start\_time\_in\_seconds": 2.4,             "duration\_in\_seconds": 5.1,             "segment\_type": "AUDIO"           },           {             "start\_time\_in\_seconds": 10.2,             "duration\_in\_seconds": 4.5,             "segment\_type": "VIDEO"           }         \]       }     \]   },   "id": "90012800291314" } |  |
| :---- | :---- |

| No violation found {   "copyright\_check\_information": {     "status": {       "status": "complete",       "matches\_found": false     }   },   "id": "{ig-media-id}" } |
| :---- |

# **Hashtag Search**

Find public IG Media that has been tagged with specific hashtags.

## **Limitations**

* You can query a maximum of 30 unique hashtags on behalf of an Instagram Business or Creator Account within a rolling, 7 day period. Once you query a hashtag, it will [count against this limit](https://developers.facebook.com/docs/instagram-api/reference/ig-user/recently_searched_hashtags) for 7 days. Subsequent queries on the same hashtag within this time frame will not count against your limit, and will not reset its initial query 7 day timer.  
* You cannot comment on hashtagged media objects discovered through the API.  
* Hashtags on Stories are not supported.  
* Emojis in hashtag queries are not supported.  
* The API will return a generic error for any requests that include hashtags that we have deemed sensitive or offensive.

## **Requirements**

In order to use this API, you must undergo [App Review](https://developers.facebook.com/docs/apps/review) and request approval for:

* the [Instagram Public Content Access](https://developers.facebook.com/docs/apps/review/feature#reference-INSTAGRAM_PUBLIC_CONTENT_ACCESS) feature  
* the [instagram\_basic](https://developers.facebook.com/docs/facebook-login/permissions#reference-instagram_basic) permission

## **Endpoints**

The Hashtag Search API consists of the following nodes and edges:

* [GET /ig\_hashtag\_search](https://developers.facebook.com/docs/instagram-api/reference/ig-hashtag-search#reading) — to get a specific hashtag's node ID  
* [GET /{ig-hashtag-id}](https://developers.facebook.com/docs/instagram-api/reference/ig-hashtag#reading) — to get data about a hashtag  
* [GET /{ig-hashtag-id}/top\_media](https://developers.facebook.com/docs/instagram-api/reference/ig-hashtag/top-media#reading) — to get the most popular photos and videos that have a specific hashtag  
* [GET /{ig-hashtag-id}/recent\_media](https://developers.facebook.com/docs/instagram-api/reference/ig-hashtag/recent-media#reading) — to get the most recently published photos and videos that have a specific hashtag  
* [GET /{ig-user-id}/recently\_searched\_hashtags](https://developers.facebook.com/docs/instagram-api/reference/ig-user/recently_searched_hashtags) — to determine the unique hashtags an Instagram Business or Creator Account has searched for in the current week

Refer to each endpoint's reference documentation for supported fields, parameters, and usage requirements.

## **Common Uses**

### **Getting Media Tagged With A Hashtag**

To get all of the photos and videos that have a specific hashtag, first use the /ig\_hashtag\_search endpoint and include the hashtag and ID of the Instagram Business or Creator Account making the query. For example, if the query is being made on behalf of the Instagram Business Account with the ID 17841405309211844, you could get the ID for the "\#coke" hashtag by performing the following query:

GET graph.facebook.com/ig\_hashtag\_search

  ?user\_id=17841405309211844

  \&q=coke

This will return the ID for the “\#Coke” hashtag node:

{

  "id" : "17873440459141021"

}

Now that you have the hashtag ID (17873440459141021), you can query its /top\_media or /recent\_media edge and include the Business Account ID to get a collection of media objects that have the “\#coke” hashtag. For example:

GET graph.facebook.com/17873440459141021/recent\_media

  ?user\_id=17841405309211844

This would return a response that looks like this:

{

  "data": \[

    {

      "id": "17880997618081620"

    },

    {

      "id": "17871527143187462"

    },

    {       

      "id": "17896450804038745"     

    }

  \]

}

**Mentions**  
Identify captions, comments, and IG Media in which an Instagram Business or Creator's alias has been tagged or @mentioned.

## **Limitations**

* Mentions on Stories are not supported.  
* Commenting on photos in which you were tagged is not supported.  
* [Webhooks](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/mentions#webhooks) will not be sent if the Media upon which the comment or @mention appears was created by an account that is set to [private](https://www.facebook.com/help/instagram/448523408565555).

## **Endpoints**

The API consists of the following endpoints:

* [GET /{ig-user-id}/tags](https://developers.facebook.com/docs/instagram-api/reference/ig-user/tags) — to get the media objects in which a Business or Creator Account has been tagged  
* [GET /{ig-user-id}?fields=mentioned\_comment](https://developers.facebook.com/docs/instagram-api/reference/ig-user/mentioned_comment#reading) — to get data about a comment that an Business or Creator Account has been @mentioned in  
* [GET /{ig-user-id}?fields=mentioned\_media](https://developers.facebook.com/docs/instagram-api/reference/ig-user/mentioned_media#reading) — to get data about a media object on which a Business or Creator Account has been @mentioned in a caption  
* [POST /{ig-user-id}/mentions](https://developers.facebook.com/docs/instagram-api/reference/ig-user/mentions#creating) — to reply to a comment or media object caption that a Business or Creator Account has been @mentioned in by another Instagram user

Refer to each endpoint reference document for usage instructions.

## **Webhooks**

Subscribe to the mentions field to recieve [Instagram Webhooks](https://developers.facebook.com/docs/instagram-api/guides/webhooks) notifications whenever an Instagram user mentions an Instagram Business or Creator Account. Note that we do not store Webhooks notification data, so if you set up a Webhook that listens for mentions, you should store any received data if you plan on using it later.

## **Examples**

### **Listening for and Replying to Comment @Mentions**

You can listen for comment @mentions and reply to any that meet your criteria:

1. Set up an [Instagram webhook](https://developers.facebook.com/docs/instagram-api/guides/webhooks) that's subscribed to the mentions field.  
2. Set up a script that can parse the Webhooks notifications and identify comment IDs.  
3. Use the IDs to query the GET /{ig-user-id}/mentioned\_comment endpoint to get more information about each comment.  
4. Analyze the returned results to identify any comments that meet your criteria.  
5. Use the POST /{ig-user-id}/mentions endpoint to [reply to those comments](https://developers.facebook.com/docs/instagram-api/reference/ig-user/mentions#creating).

The reply will appear as a sub-thread comment on the comment in which the Business or Creator Account was mentioned.

### **Listening for and Replying to Caption @Mentions**

You can listen for caption @mentions and reply to any that meet your criteria:

1. Set up an [Instagram webhook](https://developers.facebook.com/docs/instagram-api/guides/webhooks) that's subscribed to the mentions field.  
2. Set up a script that can parse the Webhooks notifications and identify media IDs.  
3. Use the IDs to query the GET /{ig-user-id}/mentioned\_media endpoint to get more information about each media object.  
4. Analyze the returned results to identify media objects with captions that meet your criteria.  
5. Use the POST /{ig-user-id}/mentions endpoint to [comment on those media objects](https://developers.facebook.com/docs/instagram-api/reference/ig-user/mentions#creating).

**Product Tagging**  
You can use the Instagram Graph API to create and manage [Instagram Shopping Product Tags](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F2022466637835789%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEeoBespfm9SmXkWy0XNoxUTjGvZPngaXSqlXTpVr5SskN_6hSFYDntJwJltRM_aem_9d8joR57Idx3EKMyagYDDg&h=AT0prTF3M7uag6lSG_BETrj3-2SE78bwtITyudwTk3r8dJB6IOzLqyeAhksIbgsE27cSTf9mqCU9L3dqyPbRQuuESyShr_XJAqEn3jOLj40by2RRPhHt02L_XP5iAfSpj6ZozX8fLEY) on an Instagram Business's [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media/).

Note: Beginning August 10, 2023, some businesses without checkout-enabled Shops will no longer be able to tag their products using the Content Publishing API. This will impact both API and native interfaces, and will remove tags to products from previous posts.Customers will still be able to tag products from Shops with checkout enabled on Facebook and Instagram. You can still refer to shopping\_product\_tag\_eligibility field to check if an Instagram account is eligible to tag their products using Content Publishing API.

The general flow for tagging products is:

1. Check if the Instagram Business is [eligible for product tagging](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#get-eligibility).  
2. If the Instagram Business is eligible, [get its product catalogs](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#get-available-catalogs).  
3. [Search each catalog for products](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#get-catalog-products) that are eligible for tagging.  
4. [Create a tagged media container](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#post-media).  
5. [Publish the tagged media container](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#post-media-publish).

## **Limitations**

* All [content publishing limitations](https://developers.facebook.com/docs/instagram-api/guides/content-publishing#limitations) apply to product tagging.  
* Product tagging is not supported for Stories and Live.  
* Product tagging is not supported for Instagram Creator accounts.  
* Accounts are limited to 25 tagged media posts within a 24 hour period. Carousel albums count as a single post.

## **Requirements**

* Refer to each endpoint's reference document to determine which permissions, features, and [User](https://developers.facebook.com/docs/facebook-login/access-tokens/#usertokens) access tokens are required for each operation.  
* The Instagram Business account (IG User) that owns the IG Media (to be tagged) must have an approved [Instagram Shop](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F1187859655048322%2F%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEeeWczKj8-YBgCyqC2X6J6w19ci4yUsynz2uHLkLKEUmSw9CGxo5BJK9fnu_w_aem_zUuF8HrSW-kL2V2Uz-uqQg&h=AT3uzjNs4WYDWjuUsmQ9ZgcU4AbQ-GkIr8Vigsor9VBbN7hgZ6YZK8DVodmjlZKDqEnrSc8pZ5xSvHUIuPCTmvXEuOge75-FsjrpKlHcNWp2Sz3Xpbi06MhcxhaF-eum-sUb-TbWgF0) with a product catalog containing products. In-app and external Instagram Shop [checkout methods](https://www.facebook.com/business/help/449169642911614) are supported.  
* The app user must have an [admin role](https://www.facebook.com/business/help/442345745885606) on the [Business Manager](https://business.facebook.com/) that owns the Instagram Shop whose products are being used to tag media.  
* In order to request [App Review](https://developers.facebook.com/docs/app-review) approval for the [instagram\_shopping\_tag\_products](https://developers.facebook.com/docs/permissions/reference/instagram_shopping_tag_products) and [catalog\_management](https://developers.facebook.com/docs/permissions/reference/catalog_management) permissions, which are required by several product tagging endpoints, your app must be associated with a [verified business](https://developers.facebook.com/docs/development/release/business-verification).

## **Endpoints**

* [GET /{ig-user-id}](https://developers.facebook.com/docs/instagram-api/reference/ig-user#read) — Check the app user's tagging eligibility.  
* [GET /{ig-user-id}/available\_catalogs](https://developers.facebook.com/docs/instagram-api/reference/ig-user/available_catalogs#reading) — Get a list of the app user's product catalogs.  
* [GET /{ig-user-id}/catalog\_product\_search](https://developers.facebook.com/docs/instagram-api/reference/ig-user/catalog_product_search#reading) — Get a list of tag eligible products in the app user's catalog.  
* [POST /{ig-user-id}/media](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media#creating) — Create a tagged media container (step 1 of publishing process).  
* [POST /{ig-user-id}/media\_publish](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media_publish) — Publish a tagged media container (step 2 of publishing process).  
* [GET /{ig-media-id}/product\_tags](https://developers.facebook.com/docs/instagram-api/reference/ig-media/product_tags#reading) — Get tags on published IG Media.  
* [POST /{ig-media-id}/product\_tags](https://developers.facebook.com/docs/instagram-api/reference/ig-media/product_tags#creating) — Create or update tags on published IG Media.  
* [GET /{ig-user-id}/product\_appeal](https://developers.facebook.com/docs/instagram-api/reference/ig-user/product_appeal#reading) — Get product appeal information.  
* [POST /{ig-user-id}/product\_appeal](https://developers.facebook.com/docs/instagram-api/reference/ig-user/product_appeal#creating) — Appeal a product rejection.  
* [GET /{ig-media-id}/children](https://developers.facebook.com/docs/instagram-api/reference/ig-media/children#read) — Get a list of child IG Media in a carousel IG Media.

## **Get User Eligibility**

Request the shopping\_product\_tag\_eligibility field on the [IG User](https://developers.facebook.com/docs/instagram-api/reference/ig-user) endpoint to determine if the Instagram Business account has set up an [Instagram Shop](https://help.instagram.com/1187859655048322/?fbclid=IwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEeeWczKj8-YBgCyqC2X6J6w19ci4yUsynz2uHLkLKEUmSw9CGxo5BJK9fnu_w_aem_zUuF8HrSW-kL2V2Uz-uqQg). Accounts that have not set up an Instagram Shop are ineligible for product tagging.

GET /{ig-user-id}?fields=shopping\_product\_tag\_eligibility

Returns true if the Instagram Business account has been associated with a [Business Manager's](https://business.facebook.com/) approved Instagram Shop, otherwise returns false.

#### **Sample Request**

curl \-i \-X GET \\

 "https://graph.facebook.com/v24.0/90010177253934?fields=shopping\_product\_tag\_eligibility\&access\_token=EAAOc..."

#### **Sample Response**

{

  "shopping\_product\_tag\_eligibility": true,

  "id": "90010177253934"

}

## **Get Catalogs**

Use the [IG User Available Catalogs](https://developers.facebook.com/docs/instagram-api/reference/ig-user/available_catalogs) endpoint to get the Instagram Business account's product catalog.

GET /{ig-user-id}/available\_catalogs

Returns an array of catalogs and their metadata. Responses can include the following catalog fields:

* catalog\_id — Catalog ID.  
* catalog\_name — Catalog name.  
* shop\_name — Shop name.  
* product\_count — Total number of products in the catalog.

#### **Limitations**

Collaborative catalogs such as shopping partner catalogs or affiliate creator catalogs are not supported.

#### **Sample Request**

curl \-i \-X GET \\

 "https://graph.facebook.com/v24.0/90010177253934?fields=available\_catalogs\&access\_token=EAAOc..."

#### **Sample Response**

{

  "available\_catalogs": {

    "data": \[

      {

        "catalog\_id": "960179311066902",

        "catalog\_name": "Jay's Favorite Snacks",

        "shop\_name": "Jay's Bespoke",

        "product\_count": 11

      }

    \]

  },

  "id": "90010177253934"

}

## **Get Eligible Products**

Use the [IG User Catalog Product Search](https://developers.facebook.com/docs/instagram-api/reference/ig-user/catalog_product_search) endpoint to get a collection of products in the catalog that can be used to tag media. Product variants are supported.

Although the API will not return an error when publishing a post tagged with an unapproved product, the tag will not appear on the published post until the product has been approved. Therefore, we recommend that you only allow your app users to publish posts with tags whose products have a review\_status of approved. This field is returned for each product by default when you get an app user's eligible products.

GET /{ig-user-id}/catalog\_product\_search

#### **Parameters**

* catalog\_id — (required) Catalog ID.  
* q — A string to search for in each product's name, or a product's SKU number (the Content ID column in the catalog management interface). If no string is specified, all tag-eligible products will be returned.

Returns an object containing an array of tag-eligible products and their metadata. Supports [cursor-based pagination](https://developers.facebook.com/docs/graph-api/results#cursors). Responses can include the following product fields:

* image\_url — Product image URL.  
* is\_checkout\_flow — If true, product can be purchased directly in the Instagram app. If false, product must be purchased in the app user's app/website.  
* merchant\_id — Merchant ID.  
* product\_id — Product ID.  
* product\_name — Product name.  
* retailer\_id — Retailer ID.  
* review\_status — Review status. Values can be approved, outdated, pending, rejected. An approved product can appear in the app user's [Instagram Shop](https://l.facebook.com/l.php?u=https%3A%2F%2Fhelp.instagram.com%2F1187859655048322%2F%3Ffbclid%3DIwZXh0bgNhZW0CMTEAYnJpZBExaFhjV3pSdlp3STdodnhNbAEeeWczKj8-YBgCyqC2X6J6w19ci4yUsynz2uHLkLKEUmSw9CGxo5BJK9fnu_w_aem_zUuF8HrSW-kL2V2Uz-uqQg&h=AT2OUAUURkHoo36VoD-81EaXH3giXtMI1Vxq3c_L1vlHCAyBUY7dBijkjpqzI5fAHLk1Yl0qYkz2AJsR5qTwyqd5ZY9RrE074VJ-orizaoyjKdP49P0oN33HT1td9mgDYwAbH30WaBs), but an approved status does not indicate product availability (e.g, the product could be out of stock). Only tags associated with products that have a review\_status of approved can appear on published posts.  
* product\_variants — Product IDs (product\_id) and variant names (variant\_name) of [product variants](https://developers.facebook.com/docs/marketing-api/catalog/guides/product-variants).

#### **Sample Request**

curl \-i \-X GET \\

 "https://graph.facebook.com/v24.0/90010177253934/catalog\_product\_search?catalog\_id=960179311066902\&q=gummy\&access\_token=EAAOc..."

#### **Sample Response**

{

  "data": \[

    {

      "product\_id": 3231775643511089,

      "merchant\_id": 90010177253934,

      "product\_name": "Gummy Wombats",

      "image\_url": "https://scont...",

      "retailer\_id": "oh59p9vzei",

      "review\_status": "approved",

      "is\_checkout\_flow": true,

      "product\_variants": \[

            {

              "product\_id": 5209223099160494

            },

            {

              "product\_id": 7478222675582505,

              "variant\_name": "Green Gummy Wombats"

            }

          \]

    }

  \]

}

## **Create a Tagged Container for Images or Videos**

Use the [IG User Media](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media) endpoint to create a media container for an [image](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media#create-photo-container) or [video](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media#create-video-container). Alternatively, see [Create Tagged Media Container for Reels](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#post-media-reels) or [Carousels](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#carousels).

POST /{ig-user-id}/media

#### **Parameters**

* image\_url — (required for images only) The path to the image. Your image must be on a public server.  
* user\_tags — (images only) An array of public usernames and x/y coordinates for any public Instagram users who you want to tag in the image. The array must be formatted in JSON and contain a username, x, and y property. For example, \[{username:'natgeo',x:0.5,y:1.0}\]. x and y values must be floats that originate from the top-left of the image, with a range of 0.0–1.0. Tagged users will receive a notification when the media is published.  
* video\_url — (required for videos only) The path to the video. Your video must be on a public server.  
* thumb\_offset — (videos only) The location, in milliseconds, of the frame for the video's cover thumbnail image. The default value is 0, which is the first frame of the video.  
* product\_tags — (required) An array of objects specifying the product tags to add to the image or video. You can specify up to 20 tags for photo and video feed posts and up to 20 tags total per carousel post (up to 5 per carousel slide).  
  * The tags and product IDs must be unique.  
  * Tags for out-of-stock products are supported.  
  * Each object should have the following information:

    * product\_id — (required) Product ID.  
    * x — (images only) A float that indicates percentage distance from left edge of the published media image. Value must be within 0.0–1.0 range.  
  * y — (images only) A float that indicates percentage distance from top edge of the pu blished media image. Value must be within 0.0–1.0 range.

If the operation is successful the API returns a container ID which you can use to [publish the container](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#post-media-publish).

#### **Sample Request**

curl \-i \-X POST \\

 "https://graph.facebook.com/v24.0/90010177253934/media?image\_url=https%3A%2F%2Fupl...\&location\_id=7640348500\&product\_tags=%5B%0A%20%20%7B%0A%20%20%20%20product\_id%3A'3231775643511089'%2C%0A%20%20%20%20x%3A%200.5%2C%0A%20%20%20%20y%3A%200.8%0A%20%20%7D%0A%5D\&access\_token=EAAOc..."

For reference, here is the HTML-decoded POST payload string:

https://graph.facebook.com/v12.0/90010177253934/media?image\_url=https://upl...\&location\_id=7640348500

\&product\_tags=\[

  {

    product\_id:'3231775643511089',

    x: 0.5,

    y: 0.8

  }

\]\&access\_token=EAAOc...

#### **Sample Response**

{

  "id": "17969578066508312"

}

## **Create a Tagged Container for Reels**

Use the [IG User Media](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media) endpoint to create a media container for Reels. Alternatively, see [Create Tagged Media Container](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#post-media) or [Carousels](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#carousels).

POST /{ig-user-id}/media

#### **Parameters**

* media\_type — You must specify the media type REELS.  
* video\_url — The path to the video for the Reel. Your video must be on a public server. Your video must meet the [Reels Specifications](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media#reel-specifications).  
* thumb\_offset — The location, in milliseconds, of the frame for the video's cover thumbnail image. The default value is 0, which is the first frame of the video.  
* caption — The caption for the Reel.  
* product\_tags — (required) An array of objects specifying the product tags to add to the Reel. You can specify up to 30 tags, and the tags and product IDs must be unique. Tags for out-of-stock products are supported. Each object should have the following information:

  * product\_id — (required) Product ID.  
* location\_id — The ID of a Page associated with a location that you want to tag the video with. For details, see [IG User Media Query String Parameters](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media#query-string-parameters).  
* share\_to\_feed — true to request that the video appears on both the Feed and Reels tabs. false to request that the video appears only on the Reels tab.  
* access\_token — Your [User Access Token](https://developers.facebook.com/docs/facebook-login/guides/access-tokens).

If the operation is successful the API returns a container ID which you can use to [publish the container](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#post-media-publish).

#### **Sample Request**

curl \-i \-X POST \\

 "https://graph.facebook.com/v24.0/90010177253934/media?media\_type=REELS\&video\_url=https://upl...\&product\_tags=%5B%0A%20%20%7B%0A%20%20%20%20product\_id%3A'3231775643511089'%0A%20%20%7D%0A%5D\&access\_token=EAAOc..."

For reference, here is the HTML-decoded POST payload string:

https://graph.facebook.com/v12.0/90010177253934/media?media\_type=REELS\&video\_url=https://upl...

\&product\_tags=\[

  {

    product\_id:'3231775643511089'

  }

\]\&access\_token=EAAOc...

#### **Sample Response**

{

  "id": "17969578066508312"

}

## **Publish a Tagged Media Container**

Use the [IG User Media Publish](https://developers.facebook.com/docs/instagram-api/reference/ig-user/media_publish) endpoint to publish the tagged media container. You may publish up to 25 tagged media containers on behalf of an app user within a 24 hour moving period. If you are publishing a carousel, see [Carousels](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#carousels). Only products that have a review\_status of approved will appear on published posts. If any of these products are out of stock, their tags will still appear on the published post.

POST /{ig-user-id}/media\_publish

#### **Parameters**

* creation\_id — (required) The carousel container ID.

If the operation is successful the API will return an IG Media ID.

#### **Sample Request**

curl \-i \-X POST \\

 "https://graph.facebook.com/v24.0/90010177253934/media\_publish?creation\_id=17969578066508312\&access\_token=EAAOc"

#### **Sample Response**

{

  "id": "90010778325754"

}

## **Get Tags On Media**

Use the [IG Media Product Tags](https://developers.facebook.com/docs/instagram-api/reference/ig-media/product_tags#reading) endpoint to get tags on published media.

GET /{ig-media-id}/product\_tags

Returns an array of product tags and their metadata on an [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media/). Responses can include the following product tag fields:

* product\_id — Product ID.  
* merchant\_id — Merchant ID.  
* name — Product name.  
* price\_string — Product price.  
* image\_url — Product image URL.  
* review\_status — Indicates product tag eligibility status. Values can be:  
* approved — Product is approved.  
* rejected — Product was rejected.  
* pending — Still undergoing review.  
* outdated — Product was approved but has been edited and requires reapproval.  
* "" — No review status.  
* no\_review — No review status.  
* is\_checkout — If true, product can be purchased directly through the Instagram app. If false, product can only be purchased on the merchant's website.  
* stripped\_price\_string — Product short price string (price displayed in constrained spaces, such as $100 instead of 100 USD).  
* string\_sale\_price\_string — Product sale price.  
* x — A float that indicates percentage distance from left edge of media image. Value within 0.0–1.0 range.  
* y — A float that indicates percentage distance from top edge of media image. Value within 0.0–1.0 range.

#### **Sample Request**

curl \-i \-X GET \\

 "https://graph.facebook.com/v24.0/90010778325754/product\_tags?access\_token=EAAOc..."

#### **Sample Response**

{

  "data": \[

    {

      "product\_id": 3231775643511089,

      "merchant\_id": 90010177253934,

      "name": "Gummy Wombats",

      "price\_string": "$3.50",

      "image\_url": "https://scont...",

      "review\_status": "approved",

      "is\_checkout": true,

      "stripped\_price\_string": "$3.50",

      "x": 0.5,

      "y": 0.80000001192093

    }

  \]

}

## **Tag Existing Media**

Use the [IG Media Product Tags](https://developers.facebook.com/docs/instagram-api/reference/ig-media/product_tags#creating) endpoint to create or update tags on existing [IG Media](https://developers.facebook.com/docs/instagram-api/reference/ig-media/).

POST /{ig-media-id}/product\_tags

#### **Parameters**

* updated\_tags — (required) An array of objects specifying which product tags to tag the image or video with (maximum of 5; tags and product IDs must be unique). Each object should have the following information:  
* product\_id — (required) Product ID. If the IG Media has not been tagged with this ID the tag will be added to the IG Media. If the media has already been tagged with this ID, the tag's display coordinates will be updated.  
* x — (images only, required) A float that indicates percentage distance from left edge of the published media image. Value must be within 0.0–1.0 range.  
* y — (images only, required) A float that indicates percentage distance from top edge of the published media image. Value must be within 0.0–1.0 range.

Tagging media is additive until the 5 tag limit has been reached. If the targeted media has already been tagged by a product in the request, the old tag's x and y values will be updated with their new values (a new tag will not be added).

Returns true if able to update the product, otherwise returns false.

#### **Sample Request**

curl \-i \-X POST \\

 "https://graph.facebook.com/v24.0/90010778325754/product\_tags?updated\_tags=%5B%0A%20%20%7B%0A%20%20%20%20product\_id%3A'3859448974125379'%2C%0A%20%20%20%20x%3A%200.5%2C%0A%20%20%20%20y%3A%200.8%0A%20%20%7D%0A%5D\&access\_token=EAAOc..."

For reference, here is the HTML-decoded POST payload string:

https://graph.facebook.com/v12.0/90010778325754/product\_tags?updated\_tags=\[

  {

    product\_id:'3859448974125379',

    x: 0.5,

    y: 0.8

  }

\]\&access\_token=EAAOc...

#### **Sample Response**

{

  "success": true

}

## **Appeal Product Rejection**

Use the [IG User Product Appeal](https://developers.facebook.com/docs/instagram-api/reference/ig-user/product_appeal#creating) endpoint it you want to provide a way for your app users to appeal product [rejections](https://www.facebook.com/help/instagram/494867298080532) (tags of rejected products will not appear on published posts). Although not required, we do recommend that you provide a way for app users to appeal rejections, or advise them to appeal rejections [using the Business Manager](https://www.facebook.com/business/help/494867298080532).

POST /{ig-user-id}/product\_appeal

#### **Parameters**

* appeal\_reason — (required) Explanation of why the product should be approved.  
* product\_id — (required) Product ID.

Returns true if we are able to receive your request, otherwise returns false. Response does not indicate appeal outcome.

#### **Sample Request**

curl \-i \-X POST \\

"https://graph.facebook.com/v24.0/90010177253934/product\_appeal?appeal\_reason=product%20is%20a%20toy%20and%20not%20a%20real%20weapon\&product\_id=4382881195057752\&access\_token=EAAOc..."

#### **Sample Response**

{

  "success": true

}

## **Get Appeal Status**

Use the [IG User Product Appeal](https://developers.facebook.com/docs/instagram-api/reference/ig-user/product_appeal#reading) endpoint to get the status of an appeal for a given [rejected](https://www.facebook.com/help/instagram/494867298080532) product:

GET /{ig-user-id}/product\_appeal

#### **Parameters**

* product\_id — (required) Product ID.

Returns appeal status metadata. Responses can include the following appeal fields:

* eligible\_for\_appeal — Indicates if decision can be appealed (yes if true, no if false).  
* product\_id — Product ID.  
* review\_status — Review status. Value can be:  
* approved — Product is approved.  
* rejected — Product was rejected.  
* pending — Still undergoing review.  
* outdated — Product was approved but has been edited and requires reapproval.  
* "" — No review status.  
* no\_review — No review status.

#### **Sample Request**

curl \-i \-X GET \\

 "https://graph.facebook.com/v24.0/90010177253934/product\_appeal?product\_id=4029274203846188\&access\_token=EAAOc..."

#### **Sample Response**

{

  "data": \[

    {

      "product\_id": 4029274203846188,

      "review\_status": "approved",

      "eligible\_for\_appeal": false

    }

  \]

}

## **Carousels**

You can publish carousels (albums) containing up to 10 total tagged images, videos, or a mix of the two. To do this, when performing step 1 of 3 of the [carousel posts](https://developers.facebook.com/docs/instagram-api/guides/content-publishing#carousel-posts) publishing process, simply create [tagged media containers](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/product-tagging#create-tagged-media-container) for each tagged image or video that you want to appear in the album carousel and continue with the carousel publishing processs as you normally would.

### **Get child media in a carousel**

To get the IDs of IG Media in an album carousel, use the [IG Media Children](https://developers.facebook.com/docs/instagram-api/reference/ig-media/children) endpoint.

# **Instagram Upcoming Events**

This document explains how to manage Instagram events using the Instagram API with Facebook Login, covering creation, modification and retrieval of existing events.

In this document we use "Instagram User" and "Instagram Account" interchangeable; both represent your app user's Instagram professional account.

## **Before You Start**

You'll need the following:

* The instagram\_basic permission  
* The instagram\_manage\_upcoming\_events permission  
* The ID of the app user's Instagram professional account linked to a Business

### **Limitations**

* Only supports Instagram Professional accounts linked to a Business  
* Currently only supports retrieval of events created via Ads Manager or this API  
* Intended to facilitate the creation of reminder ads

## **Create a New Event**

To create a new event, send a POST request to the /\<IG\_USER\_ID\>/upcoming\_events endpoint, where \<IG\_USER\_ID\> is the ID for your app user's Instagram professional account, including the following parameters:

* title  
* start\_time  
* notification\_subtypes (optional)  
* end\_time (optional)  
* notification\_target\_time (optional)

### **Request**

*Formatted for readability. Make sure to replace placeholders with your own values.*

curl \-X POST "https://graph.facebook.com/v24.0/\<IG\_USER\_ID\>/upcoming\_events" \\

  \-F 'title="Season Premiere"' \\

  \-F 'start\_time="2024-06-30T19:00:00+0000"' \\

  \-F 'notification\_subtypes=\["BEFORE\_EVENT\_1DAY", "BEFORE\_EVENT\_15MIN", "EVENT\_START"\]' \\

  \-F 'access\_token=\<ACCESS\_TOKEN\>'

On success, your app receives a JSON response containing the new event's ID.

{

  "id": "\<EVENT\_ID\>"

}

### **Parameters**

| Name | Description |
| ----- | ----- |
| end\_time ISO string | Optional. The event's end time.Note: Must not be set when setting notification\_target\_time to "EVENT\_END". |
| notification\_target\_time string | Optional. A string value specifying the part of the event relative to which notifications will be sent. Supported values are "EVENT\_START" or "EVENT\_END". If not set in the request, defaults to "EVENT\_START". When set to "EVENT\_END", the notification\_subtypes field must include the following three values in any order: \[“BEFORE\_EVENT\_2DAY”, “BEFORE\_EVENT\_1DAY”, “BEFORE\_EVENT\_1HOUR”\]. Additionally, when set to "EVENT\_END", the event end\_date must not be specified. |
| notification\_subtypes array of strings | Optional. A comma-separated list of three values that describe when notifications will be sent to event subscribers relative to the event’s start\_time. If not set in the request, defaults to "BEFORE\_EVENT\_1DAY", "BEFORE\_EVENT\_15MIN", and "EVENT\_START". If set without specifying notification\_target\_time or with notification\_target\_time set to "EVENT\_START", "EVENT\_START" and "BEFORE\_EVENT\_1DAY" are required with one additional value. Possible additional values include: "AFTER\_EVENT\_1DAY" "AFTER\_EVENT\_2DAY" "AFTER\_EVENT\_3DAY" "AFTER\_EVENT\_4DAY" "AFTER\_EVENT\_5DAY" "AFTER\_EVENT\_6DAY" "AFTER\_EVENT\_7DAY" "BEFORE\_EVENT\_15MIN" Order does not matter. If notification\_target\_time is set to "EVENT\_END", the specified values here must be: \[“BEFORE\_EVENT\_2DAY”, “BEFORE\_EVENT\_1DAY”, “BEFORE\_EVENT\_1HOUR”\] |
| start\_time ISO string | Required. The event's start time. |
| title string | Required. The event's title. |

## **Retrieve an event**

To retrieve details of an existing event, send a GET request to the /\<EVENT\_ID\> endpoint.

### **Request**

*Formatted for readability. Make sure to replace placeholders with your own values.*

curl \-X GET "https://graph.facebook.com/v24.0/\<EVENT\_ID\>?access\_token=\<ACCESS\_TOKEN\>"

On success, your app receives a JSON response containing the ID, title , and start time for the event.

{

  "id": "\<EVENT\_ID\>"

  "title":"Updated Season Premier",

  "start\_time":"2024-05-11T16:00:00+0000"

}

## **Update an Existing Event**

To update the details of an existing event, send a POST request to the /\<EVENT\_ID\> and include one or more of the following parameters that you want to update:

* title  
* start\_time  
* notification\_subtypes (optional)  
* end\_time (optional)

### **Example Request**

*Formatted for readability. Make sure to replace placeholders with your own values.*

curl \-X POST "https://graph.facebook.com/v24.0/\<EVENT\_ID\>" \\

     \-F 'title="Season Premiere"' \\

     \-F 'start\_time="2024-06-30T19:00:00+0000"' \\

     \-F 'notification\_subtypes=\["BEFORE\_EVENT\_1DAY", "BEFORE\_EVENT\_15MIN", "EVENT\_START"\]' \\

     \-F 'access\_token=\<ACCESS\_TOKEN\>'

On success, your app receives a JSON response containing the ID for the event.

{

  "id": "\<EVENT\_ID\>"

}

## **Retrieve all Upcoming Events**

To retrieve a list of all upcoming events, send a GET request to the /\<IG\_USER\_ID\>/upcoming\_events.

### **Request**

*Formatted for readability. Make sure to replace placeholders with your own values.*

curl \-X GET "https://graph.facebook.com/v24.0/\<IG\_USER\_ID\>/upcoming\_events?access\_token=\<ACCESS\_TOKEN\>"

On success, your app receives a JSON response containing a list of all upcoming events with the ID, title, and start time for each.

{

  "data": \[

    {

      "id": "\<EVENT\_ID\_1\>,"

      "title":"\<EVENT\_TITLE\_1\>",

      "start\_time":"2024-04-11T16:00:00+0000"

    },

    {

      "id": "\<EVENT\_ID\_2\>,"

      "title":"\<EVENT\_TITLE\_2\>",

      "start\_time":"2024-04-18T16:00:00+0000"

    },

    {

      "id": "\<EVENT\_ID\_3\>,"

      "title":"\<EVENT\_TITLE\_3\>",

      "start\_time":"2024-04-25T16:00:00+0000"

    },

  \]

}

“

[image1]: <data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAZMAAAMgAQMAAADcEx3QAAAABlBMVEUvYWxvawBDvSYbAAAAAXRSTlMAQObYZgAAAMZJREFUeF7ty7ENwCAAwLDS/5/kElDWTmVjcMZIHus5bb7f8yOGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKYYphimGKeZuswEkUQgg4SvwmQAAAABJRU5ErkJggg==>
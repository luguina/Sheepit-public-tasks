####GOAL:
The goal is to upload a file to a specific shared directory
The goal is for the user add the project's creation to give a share folder url and instead of later on downloading a zip with all frames to have it directly in google drive

####Steps to create:
- Navigate to the Google APIs Console and select _Enable APIs and Services_.
- Select _Google Drive API_.
- Click on _Try this API_ button.
- Activate the API and enter the requested data.
- If the OAuth 2.0 Client ID is not created as part of the API activation process, go to _APIs and Services > Credentials > + Create credentials_ and create a new OAuth 2.0 Client ID.
- It important to define the _Authorised redirect URIs_, that will receive the proper user token once the access to Google Drive is accepted by the user.

####Notes:
- To properly execute the software the Google Client Library is required. To install the required libraries use:
 ```
 _composer require google/apiclient:^2.0_.
 ```
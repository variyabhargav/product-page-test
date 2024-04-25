# Project Setup Guid

Please execute below command after checkout my branch : 

- 1.composer update
- 2.php artisan migrate
- 3.php artisan storage:link
- 4.php artisan db:seed --class= CreateUserSeeder

Test APIs
- Import Postman collection from Github.
- I have added a variable and created a script for storing token in the global variable.
   If needed then please update BASEURL value according to your project path.
   After that, call generate token API and it will store the token in global variable.
- Now, You can test rest API.

API Descriptions :

- token => For generate token
- products => Get all products listings with a response as a sample response. I have also added pagination in this API. By default, it returns first page data if no params(page, limit) exist in the request.
- add-product => Create product with images. Images are not compulsory.
- edit-product => Update only product information. The image will not updated using this API. For image update or delete we have another API
- update-image => In Images directory. using this API you can add new images and delete old images with pass remove image id in remove_images[].Image id we already have when called get products API
- remove => For remove product. it will automatically remove product's images and discounts with use of foreign key constraints like onDelete cascade.

[Note]: I have checked front-end product page design and it looks like Shopify product page, in Shopify they have provided image uploads separately. So I have created a separate API for updating or removing images. we also update or remove images in edit-product. But in the current task, I have done it with separation. It depends on our requirements.

- discounts => Get All discount lists with product details. Also added pagination same as products listing API
- discount => API used For add/update discount. Add and update both manage in a single API. pass id in request when need to update discount. id will get from get all discounts API.
- delete-discount => Remove discount.

I Have written code in the controller. We also can do it by separation like making requests for validation request, Repository patterns, moving logic from controller to model

I have tested everything and it is working well as per version compatibility. If you need any help, then let me know.

Thank You

# Product Page Task

Find the requirements for your task:  
- [Backend Requirements](./requirements/backend/README.md)
- [Frontend Requirements](./requirements/frontend/README.md)

> Note: If you are applying for a full stack role. Please see both of the above requirements.
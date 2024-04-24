<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Exception;
use Log;
use Validator;
use Str;

class ProductController extends Controller
{
    public $data = [];
    public function __construct()
    {
        $this->data = [
            'status' => 400,
            'data' => [],
            'message' => '',
        ];
    }

    public function json_data()
    {
        return response()->json($this->data, 200);
    }

    public function getProducts(Request $request)
    {
        try {
            $page = $request->page ?? 1;
            $limit = $request->limit ?? 10;
            $offset = ($page - 1) * $limit;
            $products = Product::with(['images:id,product_id,path', 'discount:product_id,type,discount as amount'])
                ->select(
                    "id",
                    "name",
                    "description",
                    "price",
                    "slug",
                )
                ->offset($offset)
                ->limit($limit)
                ->orderBy('updated_at', 'DESC')
                ->get()->map(function ($product) {
                    $discounted = $price = $product->price;
                    $type = $product->discount->type ?? NULL;
                    $amount = $product->discount->amount ?? NULL;
                    if (!empty($type) && !empty($amount)) {
                        if ($type == "percent") {
                            $discounted = $price - (($price * $amount) / 100);
                        } else {
                            $discounted = $price - $amount;
                        }
                    }

                    $product->price = (object) [
                        "full" => $price,
                        "discounted" => $discounted,
                    ];
                    return $product;
                });
            // {
            //     "price": {
            //         "full": 250,
            //         "discounted": 125
            //     },
            // }
            $total = Product::count();
            $this->data['message'] = 'Success.';
            $this->data['status'] = 200;
            $this->data['data'] = $products;
            $this->data['total'] = $total;
            $this->data['next_page'] = ($total <= ($page * $limit)) ? false : true;
        } catch (Exception $e) {
            log::error('getProducts', ['message' => $e->getMessage(), "\nTraceAsString" => $e->getTraceAsString()]);
            $this->data['status'] = 400;
            $this->data['message'] = 'Something went wrong.';
        }
        return $this->json_data();
    }

    public function addProduct(Request $request)
    {
        try {
            $messages = [
                'required' => 'The :attribute field is required.',
                'image' => 'The :attribute field is must be image.',
                'mimes' => 'The :attribute field is must be jpeg,png or jpg.',
                'max' => 'The :attribute field is must be less or equal 5 MB.',
            ];

            $rules = [
                'name' => 'required|string',
                'description' => 'required|string',
                'price' => 'required|integer',
                'active' => 'required|boolean',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                $this->data['message'] = $validator->messages()->first();
                $this->data['status'] = 400;
                return $this->json_data();
            }
            $slug = Str::slug(str_replace("?", "", $request->name));
            if (Product::whereSlug($slug)->exists()) {
                $this->data['message'] = "Product already exists with slug.";
                $this->data['status'] = 400;
                return $this->json_data();
            }

            $insertProoductData = [
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'active' => $request->active,
                'slug' => $slug,
            ];

            $product = Product::create($insertProoductData);

            $images = [];
            $files = $request->file('images');
            if ($request->hasFile('images')) {
                foreach ($files as $file) {
                    $ext = $file->extension();
                    $newName = uniqid() . '_product_image_' . time() . '.' . $ext;
                    $file->storeAs('public/images', $newName);
                    $images[] = [
                        'product_id' => $product->id,
                        'path' => public_path('storage/images/' . $newName),
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
            // $images = array_filter($images, 'strlen');
            if (!empty($images)) {
                $product->images()->createMany($images);
            }
            $this->data['status'] = 200;
            $this->data['data'] = $product->id;
            $this->data['message'] = 'Success.';
        } catch (Exception $e) {
            log::error('addProduct', ['message' => $e->getMessage(), "\nTraceAsString" => $e->getTraceAsString()]);
            $this->data['status'] = 400;
            $this->data['message'] = 'Something went wrong.';
        }
        return $this->json_data();
    }

    public function updateProduct(Request $request)
    {
        try {
            $messages = [
                'required' => 'The :attribute field is required.',
            ];

            $rules = [
                'id' => 'required|exists:products,id',
                'name' => 'required|string',
                'description' => 'required|string',
                'price' => 'required|integer',
                'active' => 'required|boolean',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                $this->data['message'] = $validator->messages()->first();
                $this->data['status'] = 400;
                return $this->json_data();
            }
            $id = $request->id;
            $slug = Str::slug(str_replace("?", "", $request->name));
            if (Product::whereSlug($slug)->where('id', '!=', $id)->exists()) {
                $this->data['message'] = "Product already exists with slug.";
                $this->data['status'] = 400;
                return $this->json_data();
            }

            $insertProoductData = [
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'active' => $request->active,
                'slug' => $slug,
            ];
            Product::where('id', $id)->update($insertProoductData);

            $this->data['status'] = 200;
            $this->data['data'] = $id;
            $this->data['message'] = 'Success.';
        } catch (Exception $e) {
            log::error('updateProduct', ['message' => $e->getMessage(), "\nTraceAsString" => $e->getTraceAsString()]);
            $this->data['status'] = 400;
            $this->data['message'] = 'Something went wrong.';
        }
        return $this->json_data();
    }

    public function removeProduct(Request $request, Product $product)
    {
        try {
            $product->load('images:product_id,path');
            if ($product) {
                foreach ($product->images as $value) {
                    $file_path = $value['path'];
                    if (file_exists($file_path) && is_file($file_path)) {
                        unlink($file_path);
                    }
                }
                $product->delete();
            }
            $this->data['status'] = 200;
            $this->data['message'] = 'Success.';
        } catch (Exception $e) {
            log::error('removeProduct', ['message' => $e->getMessage(), "\nTraceAsString" => $e->getTraceAsString()]);
            $this->data['status'] = 400;
            $this->data['message'] = 'Something went wrong.';
        }
        return $this->json_data();
    }

    public function updateImages(Request $request)
    {
        try {
            $messages = [
                'required' => 'The :attribute field is required.',
                'image' => 'The :attribute field is must be image.',
                'mimes' => 'The :attribute field is must be jpeg,png or jpg.',
                'max' => 'The :attribute field is must be less or equal 5 MB.',
            ];

            $rules = [
                'id' => 'required|exists:products,id',
                'remove_images' => 'nullable|array',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                $this->data['message'] = $validator->messages()->first();
                $this->data['status'] = 400;
                return $this->json_data();
            }

            $product_id = $request->id;
            $remove_images = $request->remove_images ?? [];

            /** Remove Image */
            if (!empty($remove_images)) {
                $oldImages = ProductImage::select('path')->whereIn('id', $remove_images)->get();
                if (count($oldImages) > 0) {
                    foreach ($oldImages as $value) {
                        $file_path = $value['path'];
                        if (file_exists($file_path) && is_file($file_path)) {
                            unlink($file_path);
                        }
                    }
                    ProductImage::whereIn('id', $remove_images)->delete();
                }
            }

            /** Add new Image */
            $images = [];
            $files = $request->file('images');
            if ($request->hasFile('images')) {
                foreach ($files as $file) {
                    $ext = $file->extension();
                    $newName = uniqid() . '_product_image_' . time() . '.' . $ext;
                    $file->storeAs('public/images', $newName);
                    $images[] = [
                        'product_id' => $product_id,
                        'path' => public_path('storage/images/' . $newName),
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
            if (!empty($images)) {
                ProductImage::insert($images);
            }
            $this->data['status'] = 200;
            $this->data['data'] = $product_id;
            $this->data['message'] = 'Success.';
        } catch (Exception $e) {
            log::error('updateImages', ['message' => $e->getMessage(), "\nTraceAsString" => $e->getTraceAsString()]);
            $this->data['status'] = 400;
            $this->data['message'] = 'Something went wrong.';
        }
        return $this->json_data();
    }

    public function getDiscounts(Request $request)
    {
        try {
            $page = $request->page ?? 1;
            $limit = $request->limit ?? 10;
            $offset = ($page - 1) * $limit;
            $products = ProductDiscount::with('product')
                ->select(
                    "id",
                    "product_id",
                    "type",
                    "discount",
                    "created_at",
                    "updated_at",
                )
                ->offset($offset)
                ->limit($limit)
                ->orderBy('updated_at', 'DESC')
                ->get();
            $total = ProductDiscount::count();
            $this->data['message'] = 'Success.';
            $this->data['status'] = 200;
            $this->data['data'] = $products;
            $this->data['total'] = $total;
            $this->data['next_page'] = ($total <= ($page * $limit)) ? false : true;
        } catch (Exception $e) {
            log::error('getDiscounts', ['message' => $e->getMessage(), "\nTraceAsString" => $e->getTraceAsString()]);
            $this->data['status'] = 400;
            $this->data['message'] = 'Something went wrong.';
        }
        return $this->json_data();
    }

    public function addDiscount(Request $request)
    {
        try {
            $messages = [
                'required' => 'The :attribute field is required.',
            ];

            $rules = [
                'id' => 'nullable|exists:product_discounts,id',
                'product_id' => 'required|exists:products,id',
                'type' => 'required|in:percent,amount',
                'discount' => 'required|integer',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                $this->data['message'] = $validator->messages()->first();
                $this->data['status'] = 400;
                return $this->json_data();
            }
            $id = $request->id ?? NULL;
            if ($id) {
                $insertData = [
                    'product_id' => $request->product_id,
                    'type' => $request->type,
                    'discount' => $request->discount,
                ];
                ProductDiscount::where('id', $id)->update($insertData);
            } else {
                $insertData = [
                    'product_id' => $request->product_id,
                    'type' => $request->type,
                    'discount' => $request->discount,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $id = ProductDiscount::create($insertData)->id;
            }

            $this->data['status'] = 200;
            $this->data['data'] = $id;
            $this->data['message'] = 'Success.';
        } catch (Exception $e) {
            log::error('addProduct', ['message' => $e->getMessage(), "\nTraceAsString" => $e->getTraceAsString()]);
            $this->data['status'] = 400;
            $this->data['message'] = 'Something went wrong.';
        }
        return $this->json_data();
    }

    public function deleteDiscount(Request $request, ProductDiscount $discount)
    {
        try {
            $discount->delete();
            $this->data['status'] = 200;
            $this->data['message'] = 'Success.';
        } catch (Exception $e) {
            log::error('deleteDiscount', ['message' => $e->getMessage(), "\nTraceAsString" => $e->getTraceAsString()]);
            $this->data['status'] = 400;
            $this->data['message'] = 'Something went wrong.';
        }
        return $this->json_data();
    }

    public function useLogin(Request $request)
    {
        try {
            $credentials = array('email' => "test@gmail.com", 'password' => "demo@123");
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Login credentials are invalid.',
                ], 400);
            } else {
                return response()->json(['status' => 200, 'message' => "success", "data" => array('token' => $token)], 200);
            }
        } catch (Exception $e) {
            log::error('saveUser', ['message' => $e->getMessage(), "\nTraceAsString" => $e->getTraceAsString()]);
            return response()->json(['status' => 400, 'message' => Config::get('constant.SOMETHING_WENT_WRONG')], 200);
        }
    }
}

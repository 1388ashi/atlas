# Product
در هنگام ثبت محصول ما به همراه آن تنوع و سایز چارت و مشخصات آن  رو همزمان ثبت میکنیم
## Database
#### CreateProductsTable
````mariadb
$table->id();
$table->string('title')->index(); عنوان
$table->string('short_description')->nullable()->index(); توضیحات کوتاه
$table->text('description')->nullable(); توضیحات
$table->integer('unit_price')->index(); قیمت 
$table->integer('purchase_price')->nullable(); قیمت خریداری شده
$table->enum('discount_type', ['percentage', 'flat'])->nullable(); نوع تخفیف
$table->integer('discount')->nullable(); مقدار تخفیف
$table->string('SKU')->nullable(); کد انبار داری
$table->string('barcode')->nullable(); بارکد محصول
$table->foreignId('brand_id')->nullable()->constrained('brands')->restrictOnDelete(); کلید خارجی برندآپشنال خواهد شد
//   TODO add if in size chart در آینده به صورت 
//   images in spatie media library (sortable) 
$table->foreignId('unit_id')->constrained('units')->restrictOnDelete(); کلید خارجی نوع واحد محصول برای فروش مثال: متر یا تعداد
$table->string('meta_description')->nullable(); توضیحات برای سئو
$table->string('meta_title')->nullable(); عنوان باری سئو
$table->float('low_stock_quantity_warning' , 25 , 10)->unsigned(); نشون دادن اختار درحال تمام شدن محصول
$table->boolean('show_quantity')->default(false); نشون دادن تعداد
$table->integer('quantity')->unsigned(); تعداد محصول
$table->enum('status', ['draft', 'soon', 'published', 'available', 'unavailable', 'out_of_stock']);
وضعیت محصول که شامل (پیش نویس،به زودی،انتشار،تایید شده،تایید نشده،ناموجود)
$table->timestamp('approved_at'); تایید شده در
$table->morphAuthors(); سازنده محصول
$table->timestamps();
````


#### CreateVarietiesTable

````mariadb
$table->id();
$table->bigInteger('price')->unsigned()->nullable(); قیمت تنوع
$table->string('SKU')->nullable(); کد انبار 
$table->string('barcode')->nullable(); بارکد
//images in spatie media library (sortable)
$table->integer('purchase_price')->nullable(); قیمت خریداری شده
$table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
کلید خارجی محصول(این تنوع برای کدام محصول است)
$table->enum('discount_type' , ['percentage', 'flat'])->nullable(); نوع تخفیف
$table->integer('discount')->unsigned()->nullable(); مقداز تخفیف
$table->integer('quantity')->nullable()->unsigned();  تعداد
$table->timestamps();
````

#### CreateAttributeVarietyTable [Pivot]
````mariadb
$table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
کلید خارجی ویژگی متصل شده به تنوع 
$table->foreignId('variety_id')->constrained('varieties')->cascadeOnDelete();
کلید خارجی تنوع
$table->foreignId('attribute_value_id')->nullable()->constrained('attribute_values')->nullOnDelete();
اگر ویژگی به صورت انتخابی بود مقدار انتخاب شده دراینجا قرار میگیرد
$table->string('value')->nullable();
اگر ویژگی به صورت متن بود (text) مقدار وارد شده در اینجا قرار میگیرد
$table->timestamps();
````

#### CreateSpecificationsTable

````mariadb
$table->id();
$table->string('name')->index(); نام
$table->string('label')->nullable(); برچسب 
$table->string('group')->default('عمومی'); گروه آن (مثال صفحه نمایش)
$table->enum('type', Specification::getAvailableTypes()); 
 نوع انتخابی مقدار به سه صورت (select,multiSelect,text)
$table->boolean('required')->default(0); اجباری بودن آن
$table->boolean('public')->default(0); در همه دسته بندی ها قابل مشاهده بودن؟
$table->boolean('show_filter')->default(1); توی فیلتر محصول نشان داده شود؟
$table->boolean('status')->default(1); وضعیت آن
$table->authors(); سازنده
$table->timestamps();
````

#### CreateSpecificationValuesTable
````mariadb
$table->id();
$table->foreignId('specification_id')->constrained()->cascadeOnDelete();
کلید خارجی مشخصات که مقادیر را به آن وصل میکند
$table->string('value')->index(); مقدار آن بسته به نوع انتخاب شده
$table->boolean('selected')->default(0); همیشه به صورت انتخاب شده باشد
$table->timestamps();
````

#### CreateSizeChartsTable 
Module SizeChart
````mariadb
$table->id();
$table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
$table->string('title');
$table->json('chart');
$table->timestamps();
````


## Store Product

````php
 try {
    DB::beginTransaction();

    ثبت کرد تمام مشخصات محصول
    $product->fill($request->product); 
    اتصال واحد به محصول
    $product->unit()->associate($request->product['unit_id']);
    اتصال برند به محصول
    $product->brand()->associate($request->product['brand_id']);
    سیو محصول
    $product->save();
    اگر محصول عکسی داشت آن را ثبت میکنیم
    if ($request->filled('product.images')){
        $product->addImages($request->product['images']);
    }
    تگ های محصول را به آن وصل میکنیم
    $product->attachTags($request->product['tags']);
    مشخصات محصول را به آن وصل میکنیم
    $product->assignSpecifications($request->product);
    سایز چارت های محصول را وصل میکنیم
    $product->assignSizeChart($request->product);
    /**
     * Insert Product Variety
     * Varieties are created with the products
     * @see Product method storeVariety
     */
     و درآخر هم تنوع های محصول
    $product->assignVariety($request->product);

    و اگر محصول دسته بندی داشت آن ها را وصل میکنیم
    if (!empty($request->product['categories'])) {
        $product->categories()->attach($request->product['categories']);
    }

    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    return response()->error('مشکلی در ثبت محصول به وجود آمده است لطفا مجددا تلاش کنید: ' . $e->getMessage());
}
    لود کرد تمام رابطه ها 
    $product = $product->loadCommonRelations();

return response()->success('محصول با موفقیت ایجاد شد.', compact('product'));
````

#Not Complete

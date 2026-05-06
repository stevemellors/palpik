<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/product_repo.php';
require_once __DIR__.'/../inc/category_repo.php';

require_once __DIR__."/../inc/admin_guard.php";

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $id ? product_get($id) : ['id'=>0,'name'=>'','description'=>'','price'=>'','image'=>null,'category_id'=>null,'stock'=>0];

$cats = categories_all();
$error = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_verify();
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $category_id = ($_POST['category_id'] ?? '') !== '' ? (int)$_POST['category_id'] : null;
    $stock       = (int)($_POST['stock'] ?? 0);

    if ($name === '') $error = 'Name is required';
    if ($price <= 0)  $error = $error ?: 'Price must be greater than 0';

    // --- Image upload (robust) ---
    $imageName = $item['image'] ?? null;
    $uploadDir = __DIR__.'/../uploads';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }
    if (!is_writable($uploadDir)) {
        $error = $error ?: 'Upload folder not writable: '.$uploadDir;
    }

    if (!$error && !empty($_FILES['image']['name'])) {
        $up  = $_FILES['image'];
        if ($up['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($up['name'], PATHINFO_EXTENSION));
            $allowedExts  = ['jpg','jpeg','png','webp','gif'];
            $allowedMimes = ['image/jpeg','image/png','image/webp','image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $up['tmp_name']);
            finfo_close($finfo);
            if (!in_array($ext, $allowedExts) || !in_array($mime, $allowedMimes)) {
                $error = 'Invalid image type (allowed: jpg, jpeg, png, webp, gif)';
            } else {
                $safeName  = preg_replace('/[^a-z0-9_-]+/i', '_', pathinfo($up['name'], PATHINFO_FILENAME));
                $newImageName = time() . '_' . $safeName . '.' . $ext;
                $dest = $uploadDir.'/'.$newImageName;
                if (!move_uploaded_file($up['tmp_name'], $dest)) {
                    $error = 'Failed to save uploaded image (check folder perms).';
                } else {
                    if ($imageName && $imageName !== $newImageName) {
                        $oldPath = $uploadDir.'/'.basename($imageName);
                        if (file_exists($oldPath)) @unlink($oldPath);
                    }
                    $imageName = $newImageName;
                }
            }
        } elseif ($up['error'] !== UPLOAD_ERR_NO_FILE) {
            $map = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE    => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload',
            ];
            $error = 'Upload error: '.($map[$up['error']] ?? ('Code '.$up['error']));
        }
    }
    // --- end upload ---

    if (!$error) {
        $data = [
            'category_id' => $category_id,
            'name'        => $name,
            'description' => $description ?: null,
            'price'       => $price,
            'image'       => $imageName,
            'stock'       => $stock,
        ];
        if ($id) {
            product_update($id, $data);
        } else {
            $id = product_create($data);
        }
        header('Location: /admin/products.php');
        exit;
    }
}
?><!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $id?'Edit':'New' ?> Product • <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/styles.css">
<link rel="stylesheet" href="/assets/theme-override.css">
<script src="/assets/theme.js" defer></script>
</head><body class="container">
<h1><?= $id?'Edit':'New' ?> Product</h1>
<?php if ($error): ?><p class="text-danger"><?= h($error) ?></p><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form" style="max-width:640px;">
  <?= csrf_field() ?>
  <label>Name
    <input name="name" value="<?= h($item['name']) ?>" required>
  </label>
  <label>Description
    <textarea name="description" rows="4"><?= h($item['description'] ?? '') ?></textarea>
  </label>
  <div class="grid">
    <div class="card">
      <label>Price
        <input type="number" name="price" step="0.01" min="0.01" value="<?= h((string)$item['price']) ?>" required>
      </label>
    </div>
    <div class="card">
      <label>Stock
        <input type="number" name="stock" step="1" min="0" value="<?= h((string)($item['stock'] ?? 0)) ?>">
      </label>
    </div>
  </div>
  <label>Category
    <select name="category_id">
      <option value="">— None —</option>
      <?php foreach (categories_all() as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)($item['category_id'] ?? 0) === (int)$c['id']) ? 'selected':'' ?>>
          <?= h($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="card" style="margin-top:10px;">
    <div class="kicker">Image</div>
    <?php if (!empty($item['image'])): ?>
      <p>Current: <img src="/uploads/<?= h($item['image']) ?>" alt="" style="height:80px;border-radius:8px;"></p>
    <?php endif; ?>
    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.gif">
  </div>

  <div style="display:flex;gap:8px;margin-top:12px;">
    <button class="btn" type="submit"><?= $id?'Save changes':'Create product' ?></button>
    <a class="btn" href="/admin/products.php">Cancel</a>
  </div>
</form>
</body></html>

<?php
declare(strict_types=1);
require_once __DIR__ . '/cabanatuan_barangays.php';

function normalize_single_line(string $value, int $maxLength): string
{
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    return mb_substr($value, 0, $maxLength);
}

function normalize_multiline(string $value, int $maxLength): string
{
    $value = trim(str_replace(["\r\n", "\r"], "\n", $value));
    return mb_substr($value, 0, $maxLength);
}

function validate_listing_data(array $input): array
{
    $allowedTypes = ['apartment', 'dorm', 'boarding_house'];
    $allowedGender = ['female_only', 'male_only', 'mixed'];

    $data = [
        'title' => normalize_single_line((string)($input['title'] ?? ''), 150),
        'property_type' => (string)($input['property_type'] ?? ''),
        'location' => trim((string)($input['location'] ?? '')),
        'price' => trim((string)($input['price'] ?? '')),
        'capacity' => trim((string)($input['capacity'] ?? '')),
        'gender_preference' => (string)($input['gender_preference'] ?? ''),
        'description' => normalize_multiline((string)($input['description'] ?? ''), 5000),
    ];

    $errors = [];

    if ($data['title'] === '' || mb_strlen($data['title']) < 3) {
        $errors[] = 'Property title must be at least 3 characters.';
    } elseif (!preg_match("/^[\p{L}\p{N}\s.,'()#&\/-]+$/u", $data['title'])) {
        $errors[] = 'Property title contains invalid characters.';
    }

    if (!is_valid_cabanatuan_barangay($data['location'])) {
        $errors[] = 'Please select a valid Cabanatuan City barangay.';
    }

    if (!in_array($data['property_type'], $allowedTypes, true)) {
        $errors[] = 'Please select a valid property type.';
    }

    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $data['price'])) {
        $errors[] = 'Price must be a valid positive amount.';
    } else {
        $price = (float)$data['price'];
        if ($price <= 0 || $price > 10000000) {
            $errors[] = 'Price must be greater than 0 and within the allowed range.';
        } else {
            $data['price'] = number_format($price, 2, '.', '');
        }
    }

    if (!preg_match('/^\d{1,3}$/', $data['capacity'])) {
        $errors[] = 'Capacity must be a whole number.';
    } else {
        $capacity = (int)$data['capacity'];
        if ($capacity < 1 || $capacity > 999) {
            $errors[] = 'Capacity must be between 1 and 999 persons.';
        } else {
            $data['capacity'] = (string)$capacity;
        }
    }

    if (!in_array($data['gender_preference'], $allowedGender, true)) {
        $errors[] = 'Please select a valid gender preference.';
    }

    if ($data['description'] === '' || mb_strlen($data['description']) < 10) {
        $errors[] = 'Please provide a description with the important rental information.';
    }

    return [$data, $errors];
}

function validate_uploaded_images(array $files): array
{
    $errors = [];
    $valid = [];
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    if (!isset($files['name']) || !is_array($files['name'])) {
        return [[], []];
    }

    $count = count($files['name']);
    if ($count > MAX_PROPERTY_IMAGES) {
        $errors[] = 'You can upload a maximum of ' . MAX_PROPERTY_IMAGES . ' images.';
        return [[], $errors];
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    for ($i = 0; $i < $count; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (($files['error'][$i] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'One of the uploaded images could not be processed.';
            continue;
        }

        $size = (int)($files['size'][$i] ?? 0);
        if ($size <= 0 || $size > MAX_UPLOAD_SIZE) {
            $errors[] = 'Each image must be larger than 0 bytes and no more than 5 MB.';
            continue;
        }

        $tmp = $files['tmp_name'][$i] ?? '';
        if (!is_uploaded_file($tmp)) {
            $errors[] = 'Invalid image upload detected.';
            continue;
        }

        $mime = $finfo->file($tmp);
        if (!isset($allowed[$mime])) {
            $errors[] = 'Only JPG, PNG, and WEBP images are allowed.';
            continue;
        }

        $imageInfo = @getimagesize($tmp);
        if ($imageInfo === false) {
            $errors[] = 'One uploaded file is not a valid image.';
            continue;
        }

        $valid[] = [
            'tmp_name' => $tmp,
            'extension' => $allowed[$mime],
        ];
    }

    return [$valid, $errors];
}

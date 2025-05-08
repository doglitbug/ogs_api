<?php

#region generic
/**
 * Check to see if the provided value is blank/empty
 * @param string $value
 * @return bool
 */
function is_blank(string $value): bool
{
    return (!isset($value) || trim($value) === '');
}

/**
 * Check to see if the provided value has content
 * @param string $value
 * @return bool
 */
function has_data(string $value): bool
{
    return !is_blank($value);
}

function has_length_greater_than(string $value, int $min): bool
{
    $length = strlen($value);
    return $length > $min;
}

function has_length_less_than(string $value, int $max): bool
{
    $length = strlen($value);
    return $length < $max;
}

function has_length_exactly(string $value, int $exact): bool
{
    $length = strlen($value);
    return $length == $value;
}

/**
 * Validates string length by combining greater_than, less_than and exactly
 * @param string $value
 * @param array $options min, max, exact
 * @return bool
 */
function has_length(string $value, array $options): bool
{
    if (isset($options['min']) && !has_length_greater_than($value, $options['min'] - 1)) {
        return false;
    }

    if (isset($options['max']) && !has_length_less_than($value, $options['max'] + 1)) {
        return false;
    }

    if (isset($options['exact']) && !has_length_exactly($value, $options['exact'])) {
        return false;
    }

    return true;
}

/** Validate inclusion in a set
 * @param mixed $value
 * @param array $set
 * @return bool
 * @example 5, [1,3,5,7,9] = > true
 */
function has_inclusion_of(mixed $value, array $set): bool
{
    return in_array($value, $set);
}

/** Validate exclusion from a set
 * @param mixed $value
 * @param array $set
 * @return bool
 * @example 2, [1,3,5,7,9] => true
 */
function has_exclusion_of(mixed $value, array $set): bool
{
    return !in_array($value, $set);
}

/**
 * Check for inclusion of characters
 * @param string $value
 * @param string $required_string
 * @return bool
 * @example 'nobody@nowhere.com', 'com'
 */
function has_string(string $value, string $required_string): bool
{
    return str_contains($value, $required_string);
}

/**
 * Check email is 'valid'
 * Format: [chars]@[chars].[2+ letters]
 * @param string $value
 * @return bool
 */
function has_valid_email(string $value): bool
{
    $email_regex = '/\A[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\Z/i';
    return preg_match($email_regex, $value) === 1;
}

function has_unique_username(array $user): bool
{
    global $db;
    return $db->has_unique_username($user);
}


#endregion

#region validation
function validate_user(array $user): array
{
    global $db;
    $errors = [];

    #name
    if (is_blank($user['name'])) {
        $errors['name'] = "Name cannot be blank";
    } else if (!has_length($user['name'], ['min' => 2, 'max' => 255])) {
        $errors['name'] = "Name must be between 2 and 255 characters";
    }

    #username
    if (is_blank($user['username'])) {
        $errors['username'] = "Username cannot be blank";
    } else if (!has_length($user['username'], ['min' => 2, 'max' => 255])) {
        $errors['username'] = "Username must be between 2 and 255 characters";
    } else if (has_unique_username($user)) {
        $errors['username'] = "Username already in use";
    }

    #location
    $location_str = (string)$user['location_id'];
    $locations = array_column($db->get_locations(), 'location_id');

    if (!has_inclusion_of($location_str, $locations)) {
        $errors['location_id'] = "Location must be one of the provided options";
    }

    #description
    if (has_length_greater_than($user['description'], 2048)) {
        $errors['description'] = "Description must be less than 2048 characters";
    }

    return $errors;
}

function validate_garage(array $garage): array
{
    global $db;
    $errors = [];

    #name
    if (is_blank($garage['name'])) {
        $errors['name'] = "Name cannot be blank";
    } else if (!has_length($garage['name'], ['min' => 2, 'max' => 255])) {
        $errors['name'] = "Name must be between 2 and 255 characters";
    }

    #description
    if (has_length_greater_than($garage['description'], 2048)) {
        $errors['description'] = "Description must be less than 2048 characters";
    }

    #location
    $location_str = (string)$garage['location_id'];
    $locations = array_column($db->get_locations(), 'location_id');

    if (!has_inclusion_of($location_str, $locations)) {
        $errors['location'] = "Location must be one of the provided options";
    }

    #visible
    $garage['visible'] = 1;//TODO
    $visible_str = (string)$garage['visible'];
    if (!has_inclusion_of($visible_str, ["0", "1"])) {
        $errors['visible'] = "Visible must be true or false";
    }

    return $errors;
}

function validate_item(array $item, $files): array
{
    global $db;
    $errors = [];

    #name
    if (is_blank($item['name'])) {
        $errors['name'] = "Name cannot be blank";
    } else if (!has_length($item['name'], ['min' => 2, 'max' => 255])) {
        $errors['name'] = "Name must be between 2 and 255 characters";
    }

    #description
    if (has_length_greater_than($item['description'], 2048)) {
        $errors['description'] = "Description must be less than 2048 characters";
    }

    #images
    if ($images_errors = validate_images($files)) {
        $errors['images'] = $images_errors;
    }

    #visible
    $visible_str = (string)$item['visible'];
    if (!has_inclusion_of($visible_str, ["0", "1"])) {
        $errors['visible'] = "Visible must be true or false";
    }

    return $errors;
}

/** Check to see if uploaded image is okay
 * TODO only one image at a time so far
 * @param array $images
 * @return string|null error messages
 */
function validate_images(array $images): string|null
{
    if (empty($images)) return null;

    $phpFileUploadErrors = array(
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    );

    $acceptable_types = array(
        "image/gif", "image/png", "image/jpeg"
    );

    $file_info = new finfo(FILEINFO_MIME_TYPE);

    foreach ($images as $image) {
        switch ($image['error']) {
            case UPLOAD_ERR_OK:
                //Check size
                if ($image['size'] > 1048576 * 5) {
                    return "Image must be less than 5 MB in size";
                }
                //Check file type on actual file
                $image['type'] = $file_info->file($image['tmp_name']);
                if (!in_array($image['type'], $acceptable_types)) {
                    return "Unsupported image type: only gif, png and jpeg supported";
                }
                break;
            case UPLOAD_ERR_NO_FILE:
                break;
            case UPLOAD_ERR_INI_SIZE:
                return "Image must be less than 5 MB in size";
            default:
                //TODO Log this error for webmaster
                return $phpFileUploadErrors[$image['error']];
        }
    }

    return null;
}

#endregion

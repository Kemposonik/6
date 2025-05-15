<?php

require_once 'config.php';
require_once 'funcs.php';

// Получение данных из формы
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['mail'] ?? '';
$birth = $_POST['bdate'] ?? '';
$gender = $_POST['gender'] ?? '';
$languages = $_POST['languages'] ?? [];
$bio = $_POST['bio'] ?? '';

// ВАЛИДАЦИЯ ДАННЫХ
$errors = [];

// Проверка данных
nameVal($patterns, $errors, $error_messages, $name);
phoneVal($patterns, $errors, $error_messages, $phone);
emailVal($patterns, $errors, $error_messages, $email);
birthVal($errors, $error_messages, $birth);
genderVal($errors, $error_messages, $gender);
langVal($errors, $error_messages, $languages);
bioVal($patterns, $errors, $error_messages, $bio);
conVal($errors);

// Сохраняем данные и ошибки, если они есть
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;

    $data_to_save = [
        'name' => $name,
        'phone' => $phone,
        'mail' => $email,
        'bdate' => $birth,
        'gender' => $gender,
        'languages' => $languages,
        'bio' => $bio
    ];

    foreach ($data_to_save as $key => $value) {
        if (is_array($value)) {
            setcookie($key, json_encode($value), 0, '/');
        } else {
            setcookie($key, htmlspecialchars($value), 0, '/');
        }
    }

    header('Location: form.php');
    exit;
}

// Вспомогательная функция для call_user_func_array
function refValues($arr) {
    if (strnatcmp(phpversion(), '5.3') >= 0) {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
    return $arr;
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    try {
        $stmt = $conn->prepare("UPDATE application SET 
            name = ?, phone = ?, email = ?, dob = ?, gender = ?, bio = ?
            WHERE id = ?");
        $stmt->bind_param("ssssssi", $name, $phone, $email, $birth, $gender, $bio, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        $languageIds = [];
        if (!empty($languages)) {
            $placeholders = implode(',', array_fill(0, count($languages), '?'));
            $types = str_repeat('s', count($languages));
            $query = "SELECT id FROM languages WHERE name IN ($placeholders)";
            $stmt = $conn->prepare($query);
            $params = array_merge([$types], $languages);
            call_user_func_array([$stmt, 'bind_param'], refValues($params));
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $languageIds[] = $row['id'];
            }
            $stmt->close();
        }

        if (!empty($languageIds)) {
            $stmt = $conn->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languageIds as $langId) {
                $stmt->bind_param("ii", $_SESSION['user_id'], $langId);
                $stmt->execute();
            }
            $stmt->close();
        }

        $cookie_time = time() + (86400 * 365);
        setcookie('name', htmlspecialchars($name), $cookie_time, '/');
        setcookie('phone', htmlspecialchars($phone), $cookie_time, '/');
        setcookie('mail', htmlspecialchars($email), $cookie_time, '/');
        setcookie('bdate', htmlspecialchars($birth), $cookie_time, '/');
        setcookie('gender', htmlspecialchars($gender), $cookie_time, '/');
        setcookie('bio', htmlspecialchars($bio), $cookie_time, '/');
        setcookie('languages', json_encode($languages), $cookie_time, '/');

        $_SESSION['update_success'] = true;
        header('Location: form.php');
        exit;

    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['errors']['db'] = "Произошла ошибка при сохранении данных. Пожалуйста, попробуйте позже.";
        header('Location: form.php');
        exit;
    }
} else {
    try {
        $stmt = $conn->prepare("INSERT INTO application (name, phone, email, dob, gender, bio)
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $phone, $email, $birth, $gender, $bio);
        $stmt->execute();
        $personId = $stmt->insert_id;
        $stmt->close();

        $languageIds = [];
        if (!empty($languages)) {
            $placeholders = implode(',', array_fill(0, count($languages), '?'));
            $types = str_repeat('s', count($languages));
            $query = "SELECT id FROM languages WHERE name IN ($placeholders)";
            $stmt = $conn->prepare($query);
            $params = array_merge([$types], $languages);
            call_user_func_array([$stmt, 'bind_param'], refValues($params));
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $languageIds[] = $row['id'];
            }
            $stmt->close();
        }

        if (!empty($languageIds)) {
            $stmt = $conn->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languageIds as $langId) {
                $stmt->bind_param("ii", $personId, $langId);
                $stmt->execute();
            }
            $stmt->close();
        }

        $cookie_time = time() + (86400 * 365);
        $data_to_save = [
            'name' => $name,
            'phone' => $phone,
            'mail' => $email,
            'bdate' => $birth,
            'gender' => $gender,
            'languages' => $languages,
            'bio' => $bio
        ];

        foreach ($data_to_save as $key => $value) {
            if (is_array($value)) {
                setcookie($key, json_encode($value), $cookie_time, '/');
            } else {
                setcookie($key, htmlspecialchars($value), $cookie_time, '/');
            }
        }

        $login = 'user_' . $personId;
        $password = bin2hex(random_bytes(8));
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users(application_id, login, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $personId, $login, $hashed_password);
        $stmt->execute();
        $stmt->close();

        $_SESSION['generated_credentials'] = [
            'login' => $login,
            'password' => $password
        ];

        unset($_SESSION['errors']);
        $_SESSION['success'] = true;

        header('Location: form.php');
        exit;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['errors']['db'] = "Произошла ошибка при сохранении данных. Пожалуйста, попробуйте позже.";
        header('Location: form.php');
        exit;
    } finally {
        $conn->close();
    }
}
?>

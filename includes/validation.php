<?php
require_once __DIR__ . '/../config/config.php';

class Validator {
    
    private $errors = [];
    
    public function validate($data, $rules) {
        $this->errors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $fieldRules = explode('|', $ruleSet);
            
            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule, $data);
            }
        }
        
        return empty($this->errors);
    }
    
    private function applyRule($field, $value, $rule, $allData) {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleParam = $ruleParts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " is required";
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "Invalid email format";
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < $ruleParam) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$ruleParam} characters";
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > $ruleParam) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$ruleParam} characters";
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be numeric";
                }
                break;
                
            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be an integer";
                }
                break;
                
            case 'positive':
                if (!empty($value) && $value <= 0) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be positive";
                }
                break;
                
            case 'in':
                $allowed = explode(',', $ruleParam);
                if (!empty($value) && !in_array($value, $allowed)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be one of: " . implode(', ', $allowed);
                }
                break;
                
            case 'unique':
                list($table, $column) = explode(',', $ruleParam);
                if (!empty($value) && $this->checkUnique($table, $column, $value)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " already exists";
                }
                break;
                
            case 'match':
                if (!empty($value) && $value !== ($allData[$ruleParam] ?? null)) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " does not match";
                }
                break;
                
            case 'phone':
                if (!empty($value) && !preg_match('/^[+]?[0-9\s\-()]+$/', $value)) {
                    $this->errors[$field][] = "Invalid phone number format";
                }
                break;
                
            case 'date':
                if (!empty($value) && !strtotime($value)) {
                    $this->errors[$field][] = "Invalid date format";
                }
                break;
                
            case 'datetime':
                if (!empty($value)) {
                    $d = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
                    if (!$d || $d->format('Y-m-d H:i:s') !== $value) {
                        $this->errors[$field][] = "Invalid datetime format (Y-m-d H:i:s required)";
                    }
                }
                break;
                
            case 'alpha':
                if (!empty($value) && !ctype_alpha(str_replace(' ', '', $value))) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must contain only letters";
                }
                break;
                
            case 'alphanumeric':
                if (!empty($value) && !ctype_alnum(str_replace(' ', '', $value))) {
                    $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must contain only letters and numbers";
                }
                break;
        }
    }
    
    private function checkUnique($table, $column, $value) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
            $stmt->execute([$value]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Validation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getFirstError() {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0];
        }
        return null;
    }
    
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateFile($file, $allowedTypes, $maxSize = MAX_FILE_SIZE) {
        $errors = [];
        
        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = "Invalid file upload";
            return ['valid' => false, 'errors' => $errors];
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = "File size exceeds limit";
                return ['valid' => false, 'errors' => $errors];
            case UPLOAD_ERR_NO_FILE:
                $errors[] = "No file uploaded";
                return ['valid' => false, 'errors' => $errors];
            default:
                $errors[] = "Unknown upload error";
                return ['valid' => false, 'errors' => $errors];
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = "File size exceeds maximum allowed size";
            return ['valid' => false, 'errors' => $errors];
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = "Invalid file type";
            return ['valid' => false, 'errors' => $errors];
        }
        
        return ['valid' => true, 'mime_type' => $mimeType];
    }
}
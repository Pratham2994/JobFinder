<?php


require_once __DIR__ . '/../config/database.php';


class UserController
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }
    public function checkJobSeekerDetails()
    {
        session_start();
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return ['success' => false, 'message' => 'User not authenticated.'];
        }

        $stmt = $this->conn->prepare("SELECT seeker_id FROM job_seekers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $jobSeeker = $stmt->fetch();

        return ['success' => true, 'hasDetails' => $jobSeeker !== false];
    }


    public function jobSeekerDetails($data, $files)
    {
        if (empty($data['full_name']) || empty($data['location']) || empty($data['skills'])) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        $resume = $files['resume'] ?? null;
        if ($resume && $resume['error'] === 0) {
            $resumePath = $this->handleFileUpload($resume, 'resumes');
            if (!$resumePath) {
                return ['success' => false, 'message' => 'Failed to upload resume.'];
            }
        } else {
            return ['success' => false, 'message' => 'Resume is required.'];
        }

        $skillsArray = array_map('trim', explode(',', $data['skills']));
        $skillsJson = json_encode($skillsArray);

        session_start();
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return ['success' => false, 'message' => 'User not authenticated.'];
        }

        if ($this->saveJobSeekerDetails($data, $resumePath, $skillsJson, $userId)) {
            return ['success' => true, 'message' => 'Job seeker details saved successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to save job seeker details.'];
        }
    }

    private function saveJobSeekerDetails($data, $resumePath, $skillsJson, $userId)
    {
        $stmt = $this->conn->prepare("INSERT INTO job_seekers (user_id, full_name, location, skills, resume) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $userId,
            $data['full_name'],
            $data['location'],
            $skillsJson,
            $resumePath
        ]);
    }



    private function handleFileUpload($file, $folder)
    {
        $targetDir = __DIR__ . '/../uploads/' . $folder . '/';


        $targetFile = $targetDir . basename($file["name"]);
        if (move_uploaded_file($file["tmp_name"], $targetFile)) {

            return $targetFile;
        }

        return false;
    }



    public function checkAuthStatus()
    {
        session_start();

        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            return [
                'success' => true,
                'isLoggedIn' => true,
                'role' => $_SESSION['role'],
                'userId' => $_SESSION['user_id'],
            ];
        } else {
            return [
                'success' => false,
                'isLoggedIn' => false,
                'message' => 'User is not authenticated',
            ];
        }
    }

    public function signup($data)
    {
        $username = $data['name'];
        $email = $data['email'];
        $password = $data['password'];
        $role = $data['role'];
    
        if (empty($username)) {
            return ['success' => false, 'message' => 'Username cannot be empty.'];
        }
    
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }
    
        if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long, with at least one uppercase letter, one lowercase letter, one number, and one special character.'];
        }
    
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $passwordHash, $role])) {
            return ['success' => true, 'message' => 'Signup successful', 'role' => $role];
        } else {
            return ['success' => false, 'message' => 'Signup failed'];
        }
    }
    
    public function login($data)
    {
        $email = $data['email'];
        $password = $data['password'];

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_start();
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            error_log("Session ID: " . session_id());
            error_log("Session save path: " . ini_get("session.save_path"));


            return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
            
        } else {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
    }
    
    public function logout()
    {
        session_start();
        session_unset();    
        session_destroy();  
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
}

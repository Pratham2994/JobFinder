<?php


require_once __DIR__ . '/../config/database.php';

class JobController
{
    private $conn;


    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getJobListings()
    {
        $stmt = $this->conn->prepare("
            SELECT job_posts.job_id, job_posts.job_title, job_posts.location, job_posts.job_description, employers.company_name 
            FROM job_posts 
            JOIN employers ON job_posts.employer_id = employers.employer_id
        ");

        $stmt->execute();
        $jobListings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'data' => $jobListings];
    }
    public function applyForJob($jobId)
    {
        session_start();
        error_log("Session ID: " . session_id());

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            error_log("User not authenticated in applyForJob.");
            return ['success' => false, 'message' => 'User not authenticated.'];
        }



        try {
            $stmt = $this->conn->prepare("SELECT seeker_id FROM job_seekers WHERE user_id = ?");
            $stmt->execute([$userId]);
            $seeker = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$seeker) {
                return ['success' => false, 'message' => 'User ID not found in the job_seekers table.'];
            }

            $seekerId = $seeker['seeker_id'];

            $stmt = $this->conn->prepare("SELECT * FROM job_applications WHERE job_id = ? AND seeker_id = ?");
            $stmt->execute([$jobId, $seekerId]);
            $existingApplication = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingApplication) {
                return ['success' => false, 'message' => 'You have already applied for this job.'];
            }

            $stmt = $this->conn->prepare("INSERT INTO job_applications (job_id, seeker_id) VALUES (?, ?)");
            $stmt->execute([$jobId, $seekerId]);

            return ['success' => true, 'message' => 'Application submitted successfully.'];
        } catch (PDOException $e) {
            error_log("Database error in applyForJob: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error submitting application.'];
        }
    }



    public function getJobDetails($jobId)
    {
        try {
            $stmt = $this->conn->prepare("
            SELECT job_posts.job_id, job_posts.job_title, job_posts.location, job_posts.min_experience, 
                   job_posts.salary, job_posts.job_description, job_posts.employment_type, 
                   employers.company_name, employers.company_description 
            FROM job_posts 
            JOIN employers ON job_posts.employer_id = employers.employer_id 
            WHERE job_posts.job_id = ?
        ");
            $stmt->execute([$jobId]);
            $jobDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($jobDetails) {
                return ['success' => true, 'data' => $jobDetails];
            } else {
                return ['success' => false, 'message' => 'Job details not found'];
            }
        } catch (PDOException $e) {
            error_log("Database error in getJobDetails: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error fetching job details.'];
        }
    }

    public function addJobListing($data)
    {
        session_start();
        error_log("Session ID: " . session_id());


        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return ['success' => false, 'message' => 'User not authenticated.'];
        }

        try {
            $stmt = $this->conn->prepare("SELECT employer_id FROM employers WHERE user_id = ?");
            $stmt->execute([$userId]);
            $employer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employer) {
                return ['success' => false, 'message' => 'Employer ID not found.'];
            }

            $employerId = $employer['employer_id'];

            $stmt = $this->conn->prepare("INSERT INTO job_posts (employer_id, job_title, location, min_experience, salary, job_description, employment_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $employerId,
                $data['job_title'],
                $data['location'],
                $data['min_experience'],
                $data['salary'],
                $data['job_description'],
                $data['employment_type']
            ]);

            return ['success' => true, 'message' => 'Job listing added successfully.'];
        } catch (PDOException $e) {
            error_log("Database error in addJobListing: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error adding job listing.'];
        }
    }

    public function trackApplications()
    {
        session_start();


        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return ['success' => false, 'message' => 'User not authenticated.'];
        }

        try {
            $stmt = $this->conn->prepare("SELECT employer_id FROM employers WHERE user_id = ?");
            $stmt->execute([$userId]);
            $employer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employer) {
                return ['success' => false, 'message' => 'Employer not found.'];
            }

            $employerId = $employer['employer_id'];

            $stmt = $this->conn->prepare("
            SELECT job_applications.application_id, job_applications.application_status, job_applications.applied_at,
                   job_posts.job_title, job_seekers.seeker_id, job_seekers.full_name
            FROM job_applications
            JOIN job_posts ON job_applications.job_id = job_posts.job_id
            JOIN job_seekers ON job_applications.seeker_id = job_seekers.seeker_id
            WHERE job_posts.employer_id = ?
            ORDER BY job_applications.applied_at DESC
        ");
            $stmt->execute([$employerId]);
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Applications: " . print_r($applications, true));


            return ['success' => true, 'data' => $applications];
        } catch (PDOException $e) {
            error_log("Database error in trackApplications: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error fetching applications.'];
        }
    }

    public function getJobSeekerDetails($seekerId)
    {
        try {
            $stmt = $this->conn->prepare("
            SELECT job_seekers.full_name, job_seekers.location, job_seekers.skills, job_seekers.resume, users.email 
            FROM job_seekers 
            JOIN users ON job_seekers.user_id = users.user_id 
            WHERE job_seekers.seeker_id = ?
        ");
            $stmt->execute([$seekerId]);
            $seekerDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($seekerDetails) {
                $seekerDetails['skills'] = json_decode($seekerDetails['skills']); // Decode JSON skills
                return ['success' => true, 'data' => $seekerDetails];
            } else {
                return ['success' => false, 'message' => 'Job seeker not found'];
            }
        } catch (PDOException $e) {
            error_log("Database error in getJobSeekerDetails: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error fetching job seeker details.'];
        }
    }

    public function updateApplicationStatus($applicationId, $status)
    {
        try {
            $stmt = $this->conn->prepare("UPDATE job_applications SET application_status = ? WHERE application_id = ?");
            $stmt->execute([$status, $applicationId]);

            return ['success' => true, 'message' => 'Application status updated successfully.'];
        } catch (PDOException $e) {
            error_log("Database error in updateApplicationStatus: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error updating application status.'];
        }
    }
    public function getSeekerApplications()
    {
        session_start();

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return ['success' => false, 'message' => 'User not authenticated.'];
        }

        try {
            $stmt = $this->conn->prepare("SELECT seeker_id FROM job_seekers WHERE user_id = ?");
            $stmt->execute([$userId]);
            $seeker = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$seeker) {
                return ['success' => false, 'message' => 'Job seeker not found.'];
            }

            $seekerId = $seeker['seeker_id'];

            $stmt = $this->conn->prepare("
            SELECT job_applications.application_id, job_applications.application_status, job_applications.applied_at,
                   job_posts.job_id, job_posts.job_title, employers.company_name
            FROM job_applications
            JOIN job_posts ON job_applications.job_id = job_posts.job_id
            JOIN employers ON job_posts.employer_id = employers.employer_id
            WHERE job_applications.seeker_id = ?
            ORDER BY job_applications.applied_at DESC
        ");
            $stmt->execute([$seekerId]);
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $applications];
        } catch (PDOException $e) {
            error_log("Database error in getSeekerApplications: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error fetching applications.'];
        }
    }

    public function getJobDetailsById($jobId)
    {
        if (!$jobId) {
            return ['success' => false, 'message' => 'Job ID is required'];
        }

        try {
            $stmt = $this->conn->prepare("
            SELECT job_posts.job_id, job_posts.job_title, job_posts.location, job_posts.min_experience, 
                   job_posts.salary, job_posts.job_description, job_posts.employment_type, 
                   employers.company_name, employers.company_description 
            FROM job_posts 
            JOIN employers ON job_posts.employer_id = employers.employer_id 
            WHERE job_posts.job_id = ?
        ");
            $stmt->execute([$jobId]);
            $jobDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($jobDetails) {
                return ['success' => true, 'data' => $jobDetails];
            } else {
                return ['success' => false, 'message' => 'Job details not found'];
            }
        } catch (PDOException $e) {
            error_log("Database error in getJobDetailsById: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error fetching job details.'];
        }
    }
}

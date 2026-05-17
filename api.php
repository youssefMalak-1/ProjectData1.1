<?php
// ============================================================
//  CONFIGURATION — عدّل البيانات دي
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // اسم المستخدم
define('DB_PASS', '');           // كلمة السر
define('DB_NAME', 'student_db'); // اسم قاعدة البيانات

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ============================================================
//  CONNECT
// ============================================================
function getConn() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function ok($data)  { echo json_encode(['success' => true, 'data' => $data]); exit; }
function err($msg)  { http_response_code(400); echo json_encode(['success' => false, 'error' => $msg]); exit; }

// ============================================================
//  ROUTER
// ============================================================
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ---- TEST CONNECTION ----
    case 'ping':
        $conn = getConn();
        ok(['message' => 'Connected to MySQL']);

    // ---- INIT SCHEMA ----
    case 'init':
        $conn = getConn();
        $sql = "
        CREATE TABLE IF NOT EXISTS Students (
            student_id INT AUTO_INCREMENT PRIMARY KEY,
            f_name VARCHAR(100), l_name VARCHAR(100),
            department VARCHAR(150), phone VARCHAR(20),
            year INT, status VARCHAR(20)
        );
        CREATE TABLE IF NOT EXISTS Courses (
            course_id INT AUTO_INCREMENT PRIMARY KEY,
            course_code VARCHAR(20), course_name VARCHAR(200),
            credit_hours INT
        );
        CREATE TABLE IF NOT EXISTS Users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE, password VARCHAR(255),
            email VARCHAR(150), role VARCHAR(50),
            is_active TINYINT DEFAULT 1,
            student_id INT UNIQUE,
            FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS Enrollments (
            enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT, course_id INT,
            enroll_date DATE, semester VARCHAR(20), academic_year VARCHAR(10),
            FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS Grades (
            grade_id INT AUTO_INCREMENT PRIMARY KEY,
            enrollment_id INT, exam_type VARCHAR(50),
            marks FLOAT, grade_letter VARCHAR(5),
            FOREIGN KEY (enrollment_id) REFERENCES Enrollments(enrollment_id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS Attendance (
            attendance_id INT AUTO_INCREMENT PRIMARY KEY,
            enrollment_id INT, att_date DATE,
            status VARCHAR(20), notes TEXT,
            FOREIGN KEY (enrollment_id) REFERENCES Enrollments(enrollment_id) ON DELETE CASCADE
        );";
        // Run each statement
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
            if ($q) $conn->query($q);
        }
        ok(['message' => 'Schema ready']);

    // ============================================================
    //  STUDENTS
    // ============================================================
    case 'get_students':
        $conn = getConn();
        $where = '1=1'; $params = [];
        if (!empty($body['search'])) { $s = '%'.$body['search'].'%'; $where .= " AND (CONCAT(f_name,' ',l_name) LIKE ? OR department LIKE ? OR phone LIKE ?)"; $params = array_merge($params,[$s,$s,$s]); }
        if (!empty($body['dept']))   { $where .= ' AND department = ?'; $params[] = $body['dept']; }
        $stmt = $conn->prepare("SELECT * FROM Students WHERE $where ORDER BY student_id DESC");
        if ($params) { $stmt->bind_param(str_repeat('s', count($params)), ...$params); }
        $stmt->execute();
        ok($stmt->get_result()->fetch_all(MYSQLI_ASSOC));

    case 'save_student':
        $conn = getConn();
        $id = intval($body['student_id'] ?? 0);
        $f=$body['f_name']??''; $l=$body['l_name']??''; $d=$body['department']??'';
        $p=$body['phone']??''; $y=intval($body['year']??0); $s=$body['status']??'Active';
        if (!$f || !$l) err('First and last name required');
        if ($id) {
            $stmt=$conn->prepare('UPDATE Students SET f_name=?,l_name=?,department=?,phone=?,year=?,status=? WHERE student_id=?');
            $stmt->bind_param('ssssiis',$f,$l,$d,$p,$y,$s,$id);
        } else {
            $stmt=$conn->prepare('INSERT INTO Students (f_name,l_name,department,phone,year,status) VALUES (?,?,?,?,?,?)');
            $stmt->bind_param('ssssis',$f,$l,$d,$p,$y,$s);
        }
        $stmt->execute();
        ok(['student_id' => $id ?: $conn->insert_id]);

    case 'delete_student':
        $conn = getConn();
        $id = intval($body['student_id'] ?? 0);
        $conn->prepare('DELETE FROM Students WHERE student_id=?')->bind_param('i',$id) || err('Bad id');
        $stmt=$conn->prepare('DELETE FROM Students WHERE student_id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        ok([]);

    case 'get_departments':
        $conn = getConn();
        $r = $conn->query('SELECT DISTINCT department FROM Students ORDER BY department');
        ok(array_column($r->fetch_all(MYSQLI_ASSOC),'department'));

    // ============================================================
    //  COURSES
    // ============================================================
    case 'get_courses':
        $conn = getConn();
        ok($conn->query('SELECT * FROM Courses ORDER BY course_id')->fetch_all(MYSQLI_ASSOC));

    case 'save_course':
        $conn = getConn();
        $id=intval($body['course_id']??0); $name=$body['course_name']??''; $code=$body['course_code']??''; $h=intval($body['credit_hours']??3);
        if (!$name) err('Course name required');
        if ($id) {
            $stmt=$conn->prepare('UPDATE Courses SET course_name=?,course_code=?,credit_hours=? WHERE course_id=?');
            $stmt->bind_param('ssii',$name,$code,$h,$id);
        } else {
            $stmt=$conn->prepare('INSERT INTO Courses (course_name,course_code,credit_hours) VALUES (?,?,?)');
            $stmt->bind_param('ssi',$name,$code,$h);
        }
        $stmt->execute();
        ok(['course_id' => $id ?: $conn->insert_id]);

    case 'delete_course':
        $conn = getConn();
        $id=intval($body['course_id']??0);
        $stmt=$conn->prepare('DELETE FROM Courses WHERE course_id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        ok([]);

    case 'course_enrolled_count':
        $conn = getConn();
        $id=intval($body['course_id']??0);
        $stmt=$conn->prepare('SELECT COUNT(*) as cnt FROM Enrollments WHERE course_id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        ok($stmt->get_result()->fetch_assoc());

    // ============================================================
    //  ENROLLMENTS
    // ============================================================
    case 'get_enrollments':
        $conn = getConn();
        $rows = $conn->query("
            SELECT e.*, CONCAT(s.f_name,' ',s.l_name) AS student_name,
                   c.course_name, c.course_code
            FROM Enrollments e
            JOIN Students s ON e.student_id=s.student_id
            JOIN Courses c ON e.course_id=c.course_id
            ORDER BY e.enrollment_id DESC
        ")->fetch_all(MYSQLI_ASSOC);
        ok($rows);

    case 'save_enrollment':
        $conn = getConn();
        $id=intval($body['enrollment_id']??0);
        $sid=intval($body['student_id']??0); $cid=intval($body['course_id']??0);
        $date=$body['enroll_date']??''; $sem=$body['semester']??'Fall'; $yr=$body['academic_year']??'';
        if (!$sid||!$cid) err('Student and course required');
        if ($id) {
            $stmt=$conn->prepare('UPDATE Enrollments SET student_id=?,course_id=?,enroll_date=?,semester=?,academic_year=? WHERE enrollment_id=?');
            $stmt->bind_param('iisssi',$sid,$cid,$date,$sem,$yr,$id);
        } else {
            $stmt=$conn->prepare('INSERT INTO Enrollments (student_id,course_id,enroll_date,semester,academic_year) VALUES (?,?,?,?,?)');
            $stmt->bind_param('iisss',$sid,$cid,$date,$sem,$yr);
        }
        $stmt->execute();
        ok(['enrollment_id' => $id ?: $conn->insert_id]);

    case 'delete_enrollment':
        $conn = getConn();
        $id=intval($body['enrollment_id']??0);
        $stmt=$conn->prepare('DELETE FROM Enrollments WHERE enrollment_id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        ok([]);

    case 'get_enrollments_select':
        $conn = getConn();
        $rows = $conn->query("
            SELECT e.enrollment_id, CONCAT(s.f_name,' ',s.l_name,' - ',c.course_name) AS label
            FROM Enrollments e
            JOIN Students s ON e.student_id=s.student_id
            JOIN Courses c ON e.course_id=c.course_id
            ORDER BY s.f_name
        ")->fetch_all(MYSQLI_ASSOC);
        ok($rows);

    case 'get_course_details':
        $conn = getConn();
        $cid=intval($body['course_id']??0);
        $course=$conn->query("SELECT * FROM Courses WHERE course_id=$cid")->fetch_assoc();
        $students=$conn->query("
            SELECT s.*, e.enrollment_id, e.enroll_date, e.semester, e.academic_year,
                   (SELECT AVG(marks) FROM Grades WHERE enrollment_id=e.enrollment_id) AS avg_marks,
                   (SELECT COUNT(*) FROM Attendance WHERE enrollment_id=e.enrollment_id) AS att_total,
                   (SELECT COUNT(*) FROM Attendance WHERE enrollment_id=e.enrollment_id AND status='Present') AS att_present
            FROM Enrollments e
            JOIN Students s ON e.student_id=s.student_id
            WHERE e.course_id=$cid ORDER BY s.f_name
        ")->fetch_all(MYSQLI_ASSOC);
        ok(['course'=>$course,'students'=>$students]);

    // ============================================================
    //  GRADES
    // ============================================================
    case 'get_grades':
        $conn = getConn();
        $rows=$conn->query("
            SELECT g.*, CONCAT(s.f_name,' ',s.l_name) AS student_name, c.course_name
            FROM Grades g
            JOIN Enrollments e ON g.enrollment_id=e.enrollment_id
            JOIN Students s ON e.student_id=s.student_id
            JOIN Courses c ON e.course_id=c.course_id
            ORDER BY g.grade_id DESC
        ")->fetch_all(MYSQLI_ASSOC);
        ok($rows);

    case 'save_grade':
        $conn = getConn();
        $id=intval($body['grade_id']??0);
        $eid=intval($body['enrollment_id']??0); $type=$body['exam_type']??'Final';
        $marks=floatval($body['marks']??0); $letter=$body['grade_letter']??'';
        if (!$eid) err('Enrollment required');
        if ($id) {
            $stmt=$conn->prepare('UPDATE Grades SET enrollment_id=?,exam_type=?,marks=?,grade_letter=? WHERE grade_id=?');
            $stmt->bind_param('isssi',$eid,$type,$marks,$letter,$id);
        } else {
            $stmt=$conn->prepare('INSERT INTO Grades (enrollment_id,exam_type,marks,grade_letter) VALUES (?,?,?,?)');
            $stmt->bind_param('isds',$eid,$type,$marks,$letter);
        }
        $stmt->execute();
        ok(['grade_id' => $id ?: $conn->insert_id]);

    case 'delete_grade':
        $conn = getConn();
        $id=intval($body['grade_id']??0);
        $stmt=$conn->prepare('DELETE FROM Grades WHERE grade_id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        ok([]);

    case 'get_grades_summary':
        $conn = getConn();
        ok($conn->query('SELECT marks, grade_letter FROM Grades')->fetch_all(MYSQLI_ASSOC));

    // ============================================================
    //  ATTENDANCE
    // ============================================================
    case 'get_attendance':
        $conn = getConn();
        $rows=$conn->query("
            SELECT a.*, CONCAT(s.f_name,' ',s.l_name) AS student_name, c.course_name
            FROM Attendance a
            JOIN Enrollments e ON a.enrollment_id=e.enrollment_id
            JOIN Students s ON e.student_id=s.student_id
            JOIN Courses c ON e.course_id=c.course_id
            ORDER BY a.attendance_id DESC
        ")->fetch_all(MYSQLI_ASSOC);
        ok($rows);

    case 'save_attendance':
        $conn = getConn();
        $id=intval($body['attendance_id']??0);
        $eid=intval($body['enrollment_id']??0); $date=$body['att_date']??'';
        $status=$body['status']??'Present'; $notes=$body['notes']??'';
        if (!$eid||!$date) err('Enrollment and date required');
        if ($id) {
            $stmt=$conn->prepare('UPDATE Attendance SET enrollment_id=?,att_date=?,status=?,notes=? WHERE attendance_id=?');
            $stmt->bind_param('isssi',$eid,$date,$status,$notes,$id);
        } else {
            $stmt=$conn->prepare('INSERT INTO Attendance (enrollment_id,att_date,status,notes) VALUES (?,?,?,?)');
            $stmt->bind_param('isss',$eid,$date,$status,$notes);
        }
        $stmt->execute();
        ok(['attendance_id' => $id ?: $conn->insert_id]);

    case 'delete_attendance':
        $conn = getConn();
        $id=intval($body['attendance_id']??0);
        $stmt=$conn->prepare('DELETE FROM Attendance WHERE attendance_id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        ok([]);

    // ============================================================
    //  USERS
    // ============================================================
    case 'get_users':
        $conn = getConn();
        ok($conn->query('SELECT user_id,username,email,role,is_active,student_id FROM Users ORDER BY user_id')->fetch_all(MYSQLI_ASSOC));

    case 'save_user':
        $conn = getConn();
        $id=intval($body['user_id']??0);
        $uname=$body['username']??''; $pass=$body['password']??'';
        $email=$body['email']??''; $role=$body['role']??'Student';
        $active=intval($body['is_active']??1); $sid=$body['student_id']?intval($body['student_id']):null;
        if (!$uname||!$email) err('Username and email required');
        if ($id) {
            $stmt=$conn->prepare('UPDATE Users SET username=?,email=?,role=?,is_active=?,student_id=? WHERE user_id=?');
            $stmt->bind_param('sssiii',$uname,$email,$role,$active,$sid,$id);
            $stmt->execute();
            if ($pass) { $stmt2=$conn->prepare('UPDATE Users SET password=? WHERE user_id=?'); $stmt2->bind_param('si',$pass,$id); $stmt2->execute(); }
        } else {
            $stmt=$conn->prepare('INSERT INTO Users (username,password,email,role,is_active,student_id) VALUES (?,?,?,?,?,?)');
            $stmt->bind_param('ssssii',$uname,$pass,$email,$role,$active,$sid);
            $stmt->execute();
        }
        ok(['user_id' => $id ?: $conn->insert_id]);

    case 'delete_user':
        $conn = getConn();
        $id=intval($body['user_id']??0);
        $stmt=$conn->prepare('DELETE FROM Users WHERE user_id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        ok([]);

    // ============================================================
    //  DASHBOARD STATS
    // ============================================================
    case 'get_stats':
        $conn = getConn();
        $students = $conn->query('SELECT COUNT(*) as c FROM Students')->fetch_assoc()['c'];
        $courses   = $conn->query('SELECT COUNT(*) as c FROM Courses')->fetch_assoc()['c'];
        $enroll    = $conn->query('SELECT COUNT(*) as c FROM Enrollments')->fetch_assoc()['c'];
        $total_att = $conn->query('SELECT COUNT(*) as c FROM Attendance')->fetch_assoc()['c'];
        $present   = $conn->query("SELECT COUNT(*) as c FROM Attendance WHERE status='Present'")->fetch_assoc()['c'];
        $att_pct   = $total_att ? round($present/$total_att*100).'%' : '0%';
        $recent    = $conn->query('SELECT * FROM Students ORDER BY student_id DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
        $grades    = $conn->query('SELECT marks, grade_letter FROM Grades')->fetch_all(MYSQLI_ASSOC);
        ok(['students'=>$students,'courses'=>$courses,'enrollments'=>$enroll,'attendance'=>$att_pct,'recent_students'=>$recent,'grades'=>$grades]);

    default:
        err('Unknown action: ' . $action);
}

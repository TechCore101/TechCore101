<?php
// process_form.php
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize input
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';

    // Validate inputs
    if(empty($name) || empty($email) || empty($message)) {
        http_response_code(400); // Bad Request
        echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อมูลให้ครบถ้วน"]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400); // Bad Request
        echo json_encode(["status" => "error", "message" => "รูปแบบอีเมลไม่ถูกต้อง"]);
        exit;
    }

    // ==========================================
    // การตั้งค่า Email (Email Configuration)
    // ==========================================
    $to = "techcoresystems101@gmail.com"; // <-- กำหนดอีเมลที่คุณต้องการรับข้อความที่นี่
    $subject = "มีคำถาม/โปรเจกต์ใหม่จากหน้า Landing Page: " . $name;
    
    // ตั้งค่า Headers ของอีเมล
    $headers = "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // เนื้อหาอีเมล
    $email_body = "คุณได้รับการติดต่อใหม่จากหน้าเว็บไซต์:\n\n";
    $email_body .= "ชื่อผู้ติดต่อ: " . $name . "\n";
    $email_body .= "อีเมล: " . $email . "\n\n";
    $email_body .= "รายละเอียดโปรเจกต์/ข้อความ:\n" . str_replace("\n", "\n  ", $message) . "\n\n";
    $email_body .= "----------------------------------------\n";
    $email_body .= "ประทับเวลา: " . date('Y-m-d H:i:s');

    // สั่งให้ส่งอีเมลผ่านฟังก์ชัน mail() ของ PHP
    // (หมายเหตุ: สำหรับเครื่อง Local อย่าง WAMP ต้องไปตั้งค่า smtp ใน php.ini และ sendmail.ini ก่อนจึงจะส่งออกจริงได้)
    $mail_sent = @mail($to, $subject, $email_body, $headers);

    // ==========================================
    // บันทึกลง Log (สำรองข้อมูลเผื่อส่งเมลไม่สำเร็จ)
    // ==========================================
    $logEntry = sprintf(
        "[%s] Name: %s, Email: %s\nMessage: %s\nEmail Sent Status: %s\n----------------------------------------\n",
        date('Y-m-d H:i:s'), 
        $name, 
        $email, 
        str_replace("\n", "\n  ", $message),
        $mail_sent ? "Success" : "Failed (No SMTP Configuration?)"
    );
    file_put_contents('contacts_received.log', $logEntry, FILE_APPEND);

    // การตอบกลับไปยัง Frontend (หน้าเว็บ)
    if ($mail_sent) {
        echo json_encode([
            "status" => "success", 
            "message" => "ส่งอีเมลถึงเราเรียบร้อยแล้ว!"
        ]);
    } else {
        // กรณีส่งเมลไม่สำเร็จ (เซิร์ฟเวอร์ WAMP โลคอลมักจะเป็นแบบนี้) 
        // เราส่งสถานะเป็น success ใน UI ตามปกติให้ผู้เยี่ยมชมสบายใจ แต่หมายเหตุเพิ่มเติมว่าความจริงคือบันทึกลง Log แทน
        echo json_encode([
            "status" => "success", 
            "message" => "ส่งข้อความและบันทึกเข้าสู่ระบบ Log สำเร็จ! (แจ้งเตือน: หากต้องการให้แจ้งเตือนเข้าอีเมลจริงๆ กรุณาตั้งค่า SMTP ของเซิร์ฟเวอร์)"
        ]);
    }

} else {
    // กรณีที่มีการเข้าถึงไฟล์โดยทิศทางอื่นที่ไม่ใช่ POST
    http_response_code(405); // Method Not Allowed
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>

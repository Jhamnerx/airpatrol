<?php
// Datos de autenticación
$token = '8230739145:AAFeWF6kUmuLs-f7BHsflaBug-80rO6a_Xg';
$telegramApiUrl = "https://api.telegram.org/bot$token/sendMessage";

// Verificar si se recibieron los parámetros necesarios
if (isset($_GET['NUMBER']) && isset($_GET['MESSAGE'])) {
    $chatId = $_GET['NUMBER'];  // ID del chat o número de Telegram
    $message = $_GET['MESSAGE']; // Mensaje a enviar

    // Preparar los datos para la solicitud
    $data = [
        'chat_id' => $chatId,
        'text' => $message
    ];

    // Configurar la solicitud con cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telegramApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    // Ejecutar la solicitud
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Procesar la respuesta
    if ($httpCode == 200) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Mensaje enviado correctamente.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al enviar el mensaje.',
            'details' => json_decode($response, true)
        ]);
    }
} else {
    // Error: faltan parámetros
    echo json_encode([
        'status' => 'error',
        'message' => 'Faltan parámetros. Asegúrate de incluir NUMBER y MESSAGE.'
    ]);
}

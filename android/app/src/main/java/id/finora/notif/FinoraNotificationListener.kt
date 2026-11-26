package id.finora.notif

import android.app.Notification
import android.service.notification.NotificationListenerService
import android.service.notification.StatusBarNotification
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import org.json.JSONObject
import java.io.OutputStream
import java.net.HttpURLConnection
import java.net.URL
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone

class FinoraNotificationListener : NotificationListenerService() {

    override fun onNotificationPosted(sbn: StatusBarNotification) {
        val prefs = getSharedPreferences(MainActivity.PREFS, MODE_PRIVATE)
        val url = prefs.getString(MainActivity.KEY_URL, null) ?: return
        val token = prefs.getString(MainActivity.KEY_TOKEN, null) ?: return

        val extras = sbn.notification.extras
        val title = extras.getCharSequence(Notification.EXTRA_TITLE)?.toString().orEmpty()
        val text = extras.getCharSequence(Notification.EXTRA_TEXT)?.toString().orEmpty()
        val raw = listOf(title, text).filter { it.isNotBlank() }.joinToString(" ").trim()

        // Hindari mengirim notifikasi yang jelas bukan transaksi.
        val moneyPattern = Regex(
            "(rp|idr|rb|ribu|juta|\\bjt\\b|transfer|beli|bayar|gaji|masuk|cashback|top\\s?up)",
            RegexOption.IGNORE_CASE,
        )
        if (raw.isBlank() || !moneyPattern.containsMatchIn(raw)) return

        val payload = JSONObject().apply {
            put("raw_text", raw)
            put("source", "notification")
            put("package_name", sbn.packageName)
            put("received_at", SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'", Locale.US).apply {
                timeZone = TimeZone.getTimeZone("UTC")
            }.format(Date(sbn.postTime)))
        }

        CoroutineScope(Dispatchers.IO).launch {
            try {
                val connection = URL("$url/notifications/ingest").openConnection() as HttpURLConnection
                connection.requestMethod = "POST"
                connection.setRequestProperty("Content-Type", "application/json")
                connection.setRequestProperty("Accept", "application/json")
                connection.setRequestProperty("Authorization", "Bearer $token")
                connection.doOutput = true
                connection.connectTimeout = 10_000
                connection.readTimeout = 10_000

                val output: OutputStream = connection.outputStream
                output.use { it.write(payload.toString().toByteArray(Charsets.UTF_8)) }
                connection.responseCode // memicu request
                connection.disconnect()
            } catch (_: Exception) {
                // Jangan crash listener kalau server sedang tidak tersedia.
            }
        }
    }
}

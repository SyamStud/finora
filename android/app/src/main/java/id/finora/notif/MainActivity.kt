package id.finora.notif

import android.app.Activity
import android.content.Intent
import android.os.Bundle
import android.provider.Settings
import android.view.Gravity
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.TextView
import android.widget.Toast

class MainActivity : Activity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val prefs = getSharedPreferences(PREFS, MODE_PRIVATE)
        val urlInput = EditText(this).apply {
            hint = "https://domain-finora/api/v1"
            setText(prefs.getString(KEY_URL, "") ?: "")
        }
        val tokenInput = EditText(this).apply {
            hint = "Token Sanctum (mr_live / 1|xxxx)"
            setText(prefs.getString(KEY_TOKEN, "") ?: "")
        }

        val saveButton = Button(this).apply {
            text = "Simpan"
            setOnClickListener {
                prefs.edit()
                    .putString(KEY_URL, urlInput.text.toString().trim().trimEnd('/'))
                    .putString(KEY_TOKEN, tokenInput.text.toString().trim())
                    .apply()
                Toast.makeText(this@MainActivity, "Tersimpan", Toast.LENGTH_SHORT).show()
            }
        }

        val permissionButton = Button(this).apply {
            text = "Aktifkan akses notifikasi"
            setOnClickListener {
                startActivity(Intent(Settings.ACTION_NOTIFICATION_LISTENER_SETTINGS))
            }
        }

        setContentView(LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER_HORIZONTAL
            setPadding(48, 96, 48, 48)
            addView(TextView(this@MainActivity).apply {
                text = "Finora Notif"
                textSize = 24f
                setPadding(0, 0, 0, 48)
            })
            addView(urlInput)
            addView(tokenInput)
            addView(saveButton)
            addView(permissionButton)
        })
    }

    companion object {
        const val PREFS = "finora"
        const val KEY_URL = "api_url"
        const val KEY_TOKEN = "token"
    }
}

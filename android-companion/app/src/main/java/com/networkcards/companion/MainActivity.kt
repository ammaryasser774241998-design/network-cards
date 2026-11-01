package com.networkcards.companion

import android.Manifest
import androidx.fragment.app.FragmentActivity
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.provider.ContactsContract
import android.text.InputType
import android.view.Gravity
import android.widget.Button
import android.widget.CheckBox
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.TextView
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import org.json.JSONObject
import java.util.concurrent.Executor

class MainActivity : FragmentActivity() {
    private lateinit var endpointInput: EditText
    private lateinit var loginInput: EditText
    private lateinit var passwordInput: EditText
    private lateinit var statusText: TextView
    private lateinit var lockCheck: CheckBox
    private var startServiceAfterPermissions = false
    private lateinit var preferences: CompanionPreferences
    private var lockPromptShown = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        preferences = CompanionPreferences(this)
        showLoginForm()
        if (!lockPromptShown && preferences.deviceLockEnabled()) {
            lockPromptShown = true
            showDeviceLock()
        }
    }

    private fun showLoginForm() {
        val layout = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER
            setPadding(32, 32, 32, 32)
        }
        val title = TextView(this).apply {
            text = "مرافق كروت الشبكة"
            textSize = 24f
            gravity = Gravity.CENTER
        }
        endpointInput = EditText(this).apply {
            hint = "رابط API مثل https://example.com/api"
            setText(preferences.baseUrl())
            inputType = InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_URI
        }
        loginInput = EditText(this).apply { hint = "البريد الإلكتروني أو رقم الهاتف" }
        passwordInput = EditText(this).apply {
            hint = "كلمة المرور"
            inputType = 0x00000081
        }
        lockCheck = CheckBox(this).apply {
            text = "قفل التطبيق ببصمة الجهاز أو رمز قفله"
            isChecked = preferences.deviceLockEnabled()
        }
        statusText = TextView(this).apply { gravity = Gravity.CENTER }
        val loginButton = Button(this).apply {
            text = "تسجيل الدخول وربط الجهاز"
            setOnClickListener { login() }
        }
        val syncButton = Button(this).apply {
            text = "تشغيل استقبال رسائل البنك"
            setOnClickListener { startCompanionService() }
        }
        val contactsButton = Button(this).apply {
            text = "مزامنة جهات الاتصال مع CRM"
            setOnClickListener { syncContacts() }
        }
        val logoutButton = Button(this).apply {
            text = "إيقاف الخدمة وتسجيل الخروج"
            setOnClickListener { logout() }
        }

        layout.addView(title)
        layout.addView(endpointInput)
        layout.addView(loginInput)
        layout.addView(passwordInput)
        layout.addView(lockCheck)
        layout.addView(loginButton)
        layout.addView(syncButton)
        layout.addView(contactsButton)
        layout.addView(logoutButton)
        layout.addView(statusText)
        setContentView(layout)
    }

    private fun login() {
        if (lockCheck.isChecked && androidx.biometric.BiometricManager.from(this).canAuthenticate(
                androidx.biometric.BiometricManager.Authenticators.BIOMETRIC_STRONG or
                    androidx.biometric.BiometricManager.Authenticators.DEVICE_CREDENTIAL
            ) != androidx.biometric.BiometricManager.BIOMETRIC_SUCCESS
        ) {
            statusText.text = "فعّل بصمة أو رمز قفل الجهاز قبل تفعيل قفل التطبيق."
            return
        }

        preferences.setBaseUrl(endpointInput.text.toString())
        preferences.setDeviceLockEnabled(lockCheck.isChecked)
        statusText.text = "جارٍ تسجيل الدخول..."
        Thread {
            try {
                val payload = JSONObject()
                    .put("login", loginInput.text.toString())
                    .put("password", passwordInput.text.toString())
                    .put("device_name", "${Build.MANUFACTURER} ${Build.MODEL}")
                val response = ApiClient.post(this, "companion/login", payload, false)
                runOnUiThread {
                    if (response.status in 200..299) {
                        preferences.setToken(response.body.getString("token"))
                        passwordInput.text.clear()
                        statusText.text = "تم ربط الجهاز. شغّل استقبال رسائل البنك."
                    } else {
                        statusText.text = response.body.optString("message", "تعذر تسجيل الدخول.")
                    }
                }
            } catch (error: Exception) {
                runOnUiThread { statusText.text = error.message ?: "تعذر الاتصال بالخادم." }
            }
        }.start()
    }

    private fun startCompanionService() {
        if (preferences.token() == null) {
            statusText.text = "سجّل الدخول أولاً."
            return
        }
        val missing = CompanionPermissions.missing(this)
        if (missing.isNotEmpty()) {
            startServiceAfterPermissions = true
            statusText.text = "اسمح باستقبال SMS والإشعارات لتشغيل الخدمة."
            requestPermissions(missing.toTypedArray(), 100)
            return
        }
        launchCompanionService()
    }

    private fun launchCompanionService() {
        val serviceIntent = Intent(this, CompanionService::class.java)
        ContextCompat.startForegroundService(this, serviceIntent)
        statusText.text = "خدمة المزامنة تعمل. اترك الإشعار ظاهراً لاستمرار الخدمة."
    }

    private fun logout() {
        stopService(Intent(this, CompanionService::class.java))
        Thread {
            try {
                ApiClient.post(this, "companion/logout", JSONObject())
            } catch (error: Exception) {
                android.util.Log.e("CompanionSMS", "Unable to revoke the companion token", error)
            }
            preferences.clearToken()
            runOnUiThread { statusText.text = "تم إيقاف الخدمة وتسجيل الخروج." }
        }.start()
    }

    private fun syncContacts() {
        if (preferences.token() == null) {
            statusText.text = "سجّل الدخول أولاً."
            return
        }
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.READ_CONTACTS) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(arrayOf(Manifest.permission.READ_CONTACTS), 101)
            return
        }

        statusText.text = "جارٍ مزامنة جهات الاتصال..."
        Thread {
            try {
                val contacts = org.json.JSONArray()
                contentResolver.query(
                    ContactsContract.CommonDataKinds.Phone.CONTENT_URI,
                    arrayOf(
                        ContactsContract.CommonDataKinds.Phone.DISPLAY_NAME,
                        ContactsContract.CommonDataKinds.Phone.NUMBER
                    ),
                    null,
                    null,
                    null
                )?.use { cursor ->
                    val nameColumn = cursor.getColumnIndexOrThrow(ContactsContract.CommonDataKinds.Phone.DISPLAY_NAME)
                    val phoneColumn = cursor.getColumnIndexOrThrow(ContactsContract.CommonDataKinds.Phone.NUMBER)
                    while (cursor.moveToNext() && contacts.length() < 1000) {
                        val name = cursor.getString(nameColumn)?.trim().orEmpty()
                        val phone = cursor.getString(phoneColumn)?.trim().orEmpty()
                        if (name.isNotEmpty() && phone.isNotEmpty()) {
                            contacts.put(JSONObject().put("name", name).put("phone", phone))
                        }
                    }
                }

                val response = ApiClient.post(this, "companion/contacts/sync", JSONObject().put("contacts", contacts))
                runOnUiThread {
                    statusText.text = if (response.status in 200..299) {
                        "تمت المزامنة: ${response.body.optInt("created")} جديد، ${response.body.optInt("updated")} محدّث."
                    } else {
                        "تعذرت مزامنة جهات الاتصال."
                    }
                }
            } catch (error: Exception) {
                runOnUiThread { statusText.text = error.message ?: "تعذرت مزامنة جهات الاتصال." }
            }
        }.start()
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<out String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == 100) {
            val missing = CompanionPermissions.missing(this)
            if (missing.isEmpty()) {
                if (startServiceAfterPermissions) launchCompanionService()
            } else {
                statusText.text = "اسمح باستقبال SMS والإشعارات المطلوبة ثم حاول مجدداً."
            }
            startServiceAfterPermissions = false
        } else if (requestCode == 101 && grantResults.firstOrNull() == PackageManager.PERMISSION_GRANTED) {
            syncContacts()
        }
    }

    private fun showDeviceLock() {
        val executor: Executor = ContextCompat.getMainExecutor(this)
        val prompt = BiometricPrompt(this, executor, object : BiometricPrompt.AuthenticationCallback() {
            override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
            }

            override fun onAuthenticationError(errorCode: Int, errString: CharSequence) {
                finish()
            }
        })
        val promptInfo = BiometricPrompt.PromptInfo.Builder()
            .setTitle("فتح مرافق كروت الشبكة")
            .setSubtitle("استخدم بصمة الجهاز أو رمز قفله")
            .setAllowedAuthenticators(
                androidx.biometric.BiometricManager.Authenticators.BIOMETRIC_STRONG or
                    androidx.biometric.BiometricManager.Authenticators.DEVICE_CREDENTIAL
            )
            .build()
        prompt.authenticate(promptInfo)
    }
}

package com.example.grupo5.aitherapp;

import static com.example.grupo5.aitherapp.retrofit.LogicaNegocio.PostLogin;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Patterns;
import android.view.View;
import android.widget.EditText;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.biometric.BiometricPrompt;
import androidx.core.content.ContextCompat;

import com.example.grupo5.aitherapp.activitysApp.HomeActivity;
import com.example.grupo5.aitherapp.activitysApp.RegistroActivity;

import java.util.concurrent.Executor;

public class MainActivity extends AppCompatActivity {

    EditText Email, Contrasenya;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        Email = findViewById(R.id.EmailUsuarioLogin);
        Contrasenya = findViewById(R.id.ContrasenyaUsuarioLogin);

        SharedPreferences prefs = getSharedPreferences("USER_PREFS", MODE_PRIVATE);
        boolean fingerprintEnabled = prefs.getBoolean("fingerprint_enabled", false);
        boolean usuarioRegistrado = prefs.getBoolean("usuario_registrado", false);

        // Solo pedir huella si el usuario ya se registró y activó huella
        if (usuarioRegistrado && fingerprintEnabled) {
            showFingerprintLogin();
        }
    }

    public void botonRegistrarse(View v) {
        startActivity(new Intent(MainActivity.this, RegistroActivity.class));
    }

    public void botonOlvidarContrasenya(View v) {
        Toast.makeText(this, "Funcionalidad de recuperar contraseña pendiente", Toast.LENGTH_SHORT).show();
    }

    public void botonLogin(View v) {
        String email = Email.getText().toString().trim();
        String pass = Contrasenya.getText().toString().trim();

        if (email.isEmpty() || pass.isEmpty()) {
            Toast.makeText(this, "Por favor, rellena todos los campos", Toast.LENGTH_SHORT).show();
            return;
        }

        if (!Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            Toast.makeText(this, "Por favor, introduce un email válido", Toast.LENGTH_SHORT).show();
            return;
        }

        PostLogin(email, pass, this);
    }

    private void showFingerprintLogin() {
        Executor executor = ContextCompat.getMainExecutor(this);
        BiometricPrompt biometricPrompt = new BiometricPrompt(this, executor,
                new BiometricPrompt.AuthenticationCallback() {
                    @Override
                    public void onAuthenticationSucceeded(@NonNull BiometricPrompt.AuthenticationResult result) {
                        super.onAuthenticationSucceeded(result);
                        // Huella correcta → ir a Home
                        Intent intent = new Intent(MainActivity.this, HomeActivity.class);
                        startActivity(intent);
                        finish();
                    }

                    @Override
                    public void onAuthenticationError(int errorCode, @NonNull CharSequence errString) {
                        super.onAuthenticationError(errorCode, errString);
                        Toast.makeText(MainActivity.this,
                                "Huella no registrada", Toast.LENGTH_SHORT).show();
                    }
                });

        BiometricPrompt.PromptInfo promptInfo = new BiometricPrompt.PromptInfo.Builder()
                .setTitle("Login con huella")
                .setSubtitle("Coloca tu dedo en el sensor")
                .setNegativeButtonText("Cancelar")
                .build();

        biometricPrompt.authenticate(promptInfo);
    }
}


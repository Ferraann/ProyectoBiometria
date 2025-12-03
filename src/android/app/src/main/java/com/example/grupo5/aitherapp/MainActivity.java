package com.example.grupo5.aitherapp;

import static com.example.grupo5.aitherapp.retrofit.LogicaNegocio.PostLogin;

import android.content.Intent;
import android.os.Bundle;
import android.util.Patterns;
import android.view.View;
import android.widget.EditText;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import com.example.grupo5.aitherapp.activitysApp.RegistroActivity;

public class MainActivity extends AppCompatActivity {

    EditText Email, Contrasenya;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
        Email = findViewById(R.id.EmailUsuarioLogin);
        Contrasenya = findViewById(R.id.ContrasenyaUsuarioLogin);
    }

    public void botonRegistrarse(View v) {
        Intent intent = new Intent(MainActivity.this, RegistroActivity.class);
        startActivity(intent);
    }

    // Nuevo metodo para recuperar contraseña
    public void botonOlvidarContrasenya(View v) {
        Toast.makeText(this, "Funcionalidad de recuperar contraseña pendiente", Toast.LENGTH_SHORT).show();
        // Aquí pondrías el Intent para ir a la pantalla de recuperar contraseña
    }

    public void botonLogin(View v) {
        String email = Email.getText().toString().trim();
        String pass = Contrasenya.getText().toString().trim();

        if (email.isEmpty() || pass.isEmpty()) {
            Toast.makeText(this, "Por favor, rellena todos los campos", Toast.LENGTH_SHORT).show();
            return;
        }

        if (!Patterns.EMAIL_ADDRESS.matcher(Email.getText()).matches()) {
            Toast.makeText(this, "Por favor, introduce un email valido", Toast.LENGTH_SHORT).show();
            return;
        }

        PostLogin(email, pass, this);
    }
}
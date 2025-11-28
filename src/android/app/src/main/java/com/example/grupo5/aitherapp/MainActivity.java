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

// ------------------------------------------------------------------
// Fichero: MainActivity.java
// Autor: Pablo Chasi
// Fecha: 28/10/2025
// ------------------------------------------------------------------
// Descripción:
// Clase que gestiona la pantalla principal de inicio de sesión.
// Permite al usuario introducir su email y contraseña para acceder
// a la aplicación. También ofrece un botón para ir a la pantalla
// de registro. La validación principal consiste en comprobar que
// el email introducido tiene un formato válido antes de llamar a
// la lógica de negocio para realizar el login.
// ------------------------------------------------------------------
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

    public void botonLogin(View v) {
        String email = Email.getText().toString();
        String pass = Contrasenya.getText().toString();

        if (!Patterns.EMAIL_ADDRESS.matcher(Email.getText()).matches()) {
            Toast.makeText(this, "Por favor, introduce un email valido", Toast.LENGTH_SHORT).show();
            return;
        }

        PostLogin(email, pass, this);
    }
}
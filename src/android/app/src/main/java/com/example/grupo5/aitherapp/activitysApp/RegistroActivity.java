package com.example.grupo5.aitherapp.activitysApp;

import static com.example.grupo5.aitherapp.retrofit.LogicaNegocio.PostRegistro;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.util.Log;
import android.util.Patterns;
import android.view.View;
import android.widget.EditText;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.grupo5.aitherapp.R;

public class RegistroActivity extends AppCompatActivity {
    EditText Usuario, Apellidos, Email, Contrasenya, RepetirContrasenya;

    String regexTieneMayuscula = "^(?=.*[A-Z]).+$";
    String regexTieneNumeros = "^(?=.*\\d).+$";
    String regexTieneSimbologia = "^(?=.*[$@€!%*?&]).+$";
    String regexTieneMasDe8Caracteres = "^.{8,}$";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_registro);

        Usuario = findViewById(R.id.NombreUsuario);
        Apellidos = findViewById(R.id.ApellidosUsuario);
        Email = findViewById(R.id.EmailUsuario);
        Contrasenya = findViewById(R.id.ContrasenyaUsuario);
        RepetirContrasenya = findViewById(R.id.RepetirContrasenyaUsuario);

        verificarContrasenya();
    }

    public void botonEnviarDatos(View v){
        String usuario = Usuario.getText().toString().trim();
        String apellidos = Apellidos.getText().toString().trim();
        String contrasenya = Contrasenya.getText().toString().trim();
        String email = Email.getText().toString().trim();
        String repetirContrasenya = RepetirContrasenya.getText().toString().trim();

        if (usuario.isEmpty() || apellidos.isEmpty() || contrasenya.isEmpty()
                || email.isEmpty() || repetirContrasenya.isEmpty()) {
            Toast.makeText(this, "Por favor, rellena todos los campos", Toast.LENGTH_SHORT).show();
            return;
        }

        if (!Patterns.EMAIL_ADDRESS.matcher(email).matches()){
            Toast.makeText(this, "Por favor, introduce un email válido", Toast.LENGTH_SHORT).show();
            return;
        }

        if (!contrasenya.equals(repetirContrasenya)) {
            Toast.makeText(this,"Por favor, repita correctamente la contraseña", Toast.LENGTH_SHORT).show();
            return;
        }

        String regexContrasenaSegura =
                "^(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&/#€._-]).{8,}$";

        if (!contrasenya.matches(regexContrasenaSegura)) {
            Toast.makeText(this, "Por favor, introduce una contraseña segura", Toast.LENGTH_SHORT).show();
            return;
        }

        // Enviar datos al servidor
        PostRegistro(usuario, apellidos, email, contrasenya, this);

        // Guardar que hay un usuario recién registrado
        SharedPreferences prefs = getSharedPreferences("USER_PREFS", MODE_PRIVATE);
        prefs.edit().putBoolean("usuario_registrado", true).apply();

        // Mostrar mensaje para activar la cuenta
        Toast.makeText(this,
                "Registro correcto. Revisa tu correo para activar la cuenta.",
                Toast.LENGTH_LONG).show();

        // Volver al login
        finish();
    }


    private void verificarContrasenya() {
        Contrasenya.addTextChangedListener(new TextWatcher() {
            @Override public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override public void onTextChanged(CharSequence s, int start, int before, int count) {
                String texto = Contrasenya.getText().toString();
                if (texto.matches(regexTieneMayuscula)) Log.d("Regex", "Tiene mayusculas");
                if (texto.matches(regexTieneNumeros)) Log.d("Regex", "Tiene numeros");
                if (texto.matches(regexTieneMasDe8Caracteres)) Log.d("Regex", "Tiene mas de 8 caracteres");
                if (texto.matches(regexTieneSimbologia)) Log.d("Regex", "Tiene simbologia");
            }
            @Override public void afterTextChanged(Editable s) {}
        });
    }
}

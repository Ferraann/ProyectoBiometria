package com.example.grupo5.aitherapp.activitysApp;

import static com.example.grupo5.aitherapp.retrofit.LogicaNegocio.putModificarDatos;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.grupo5.aitherapp.R;
import com.example.grupo5.aitherapp.pojos.PojoUsuario;

// ------------------------------------------------------------------
// Fichero: MainActivity.java
// Autor: Pablo Chasi
// Fecha: 28/10/2025
// ------------------------------------------------------------------
// Descripción:
// Clase que se hara la función de la página  editar perfil
// su función principal es que mediante un boton puedas seleccionar
// uno de los datos que quieres modificar, cuando edita es información
// y quiere guardalor lo que se hara es modificar las base de datos
// cambiando aquellos que hemos cambiado.
// ------------------------------------------------------------------
public class EditarPerfilActivity extends AppCompatActivity {
    EditText nombre, apellidos, contrasenyaAntigua,contrasenyaNueva,repetirContrasenyaNueva,correoNuevo,repetirCorreoNuevo;
    Button botonModificarNombre,botonModificarApellido,botonModificarCorreo,botonModificarContrasenya,botonGuardarDatos;
    boolean modificaNombre,modificaApellido,modificaCorreo,modificaContrasenya;
    PojoUsuario usuario = new PojoUsuario();
    //Regex que me permite confirma que la contraseña es segura.
    String regexTieneMayuscula = "^(?=.*[A-Z]).+$";
    String regexTieneNumeros = "^(?=.*\\d).+$";
    String regexTieneSimbologia = "^(?=.*[$@€!%*?&]).+$";
    String regexTieneMasDe8Caracteres = "^.{8,}$";

    protected void onCreate(Bundle savedInstanceState){
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_editar_perfil);

        //Obtengo los edit text.
        nombre = findViewById(R.id.editarNombre);
        apellidos=findViewById(R.id.editarApellidos);
        contrasenyaAntigua = findViewById(R.id.editarContrasenyaActual);
        contrasenyaNueva = findViewById(R.id.editarContrasenyaNueva);
        repetirContrasenyaNueva = findViewById(R.id.editarRepetirContrasenyaNueva);
        correoNuevo = findViewById(R.id.editarCorreoNuevo);
        repetirCorreoNuevo = findViewById(R.id.editarRepetirCorreoNuevo);

        botonModificarNombre = findViewById(R.id.modificarNombre);
        botonModificarApellido = findViewById(R.id.modificarApellidos);
        botonModificarCorreo = findViewById(R.id.modificarCorreo);
        botonModificarContrasenya = findViewById(R.id.modificarContrasenyaAntigua);
        botonGuardarDatos = findViewById(R.id.guardarDatos);

        modificaNombre = false;
        modificaContrasenya = false;
        modificaCorreo = false;
        modificaApellido = false;
        //Recuperar datos del usuario desde SharedPreferences
        SharedPreferences prefs = getSharedPreferences("SesionUsuario", MODE_PRIVATE);
        String nombreUsuario = prefs.getString("nombre", "");
        String apellidosUsuario = prefs.getString("apellidos", "");

        // Mostrar los datos en los EditText
        nombre.setText(nombreUsuario);
        apellidos.setText(apellidosUsuario);
    }

    public void botonActivarModificarNombre(View v){

        nombre.setEnabled(!nombre.isEnabled());

        botonModificarContrasenya.setEnabled(!botonModificarContrasenya.isEnabled());
        botonModificarApellido.setEnabled(!botonModificarApellido.isEnabled());
        botonModificarCorreo.setEnabled(!botonModificarCorreo.isEnabled());
        botonGuardarDatos.setEnabled(!botonGuardarDatos.isEnabled());

        modificaNombre = true;
    }

    public void botonActivarModificarApellidos(View v){

        apellidos.setEnabled(!apellidos.isEnabled());

        botonModificarContrasenya.setEnabled(!botonModificarContrasenya.isEnabled());
        botonModificarNombre.setEnabled(!botonModificarNombre.isEnabled());
        botonModificarCorreo.setEnabled(!botonModificarCorreo.isEnabled());
        botonGuardarDatos.setEnabled(!botonGuardarDatos.isEnabled());

        modificaApellido = true;
    }

    public void botonActivarModificarContrasenya(View v){
        contrasenyaAntigua.setEnabled(!contrasenyaAntigua.isEnabled());
        contrasenyaNueva.setEnabled(!contrasenyaNueva.isEnabled());
        repetirContrasenyaNueva.setEnabled(!repetirContrasenyaNueva.isEnabled());


        botonModificarApellido.setEnabled(!botonModificarApellido.isEnabled());
        botonModificarNombre.setEnabled(!botonModificarNombre.isEnabled());
        botonModificarCorreo.setEnabled(!botonModificarCorreo.isEnabled());
        botonGuardarDatos.setEnabled(!botonGuardarDatos.isEnabled());

        modificaContrasenya = true;
    }

    public void botonActivarModificarCorreo(View v){
        correoNuevo.setEnabled(!correoNuevo.isEnabled());
        repetirCorreoNuevo.setEnabled(!repetirCorreoNuevo.isEnabled());

        botonModificarApellido.setEnabled(!botonModificarApellido.isEnabled());
        botonModificarNombre.setEnabled(!botonModificarNombre.isEnabled());
        botonModificarContrasenya.setEnabled(!botonModificarContrasenya.isEnabled());
        botonGuardarDatos.setEnabled(!botonGuardarDatos.isEnabled());

        modificaCorreo = true;
    }

    public void botonGuardarModificaciones(View v){
        SharedPreferences prefs = getSharedPreferences("SesionUsuario", MODE_PRIVATE);
        String idUsuario = prefs.getString("id", "");

        usuario.setId(idUsuario);
        usuario.setAction("modificarDatos");

        // -------------------------------------
        // VALIDAR Y AÑADIR NOMBRE
        // -------------------------------------
        if (modificaNombre) {
            String nuevoNombre = nombre.getText().toString().trim();
            if (nuevoNombre.isEmpty()) {
                Toast.makeText(this, "El nombre no puede estar vacío", Toast.LENGTH_SHORT).show();
                return;
            }
            usuario.setNombre(nuevoNombre);
        }

        // -------------------------------------
        // VALIDAR Y AÑADIR APELLIDOS
        // -------------------------------------
        if (modificaApellido) {
            String nuevosApellidos = apellidos.getText().toString().trim();
            if (nuevosApellidos.isEmpty()) {
                Toast.makeText(this, "Los apellidos no pueden estar vacíos", Toast.LENGTH_SHORT).show();
                return;
            }
            usuario.setApellidos(nuevosApellidos);
        }

        // -------------------------------------
        // VALIDAR Y AÑADIR CORREO
        // -------------------------------------
        if (modificaCorreo) {

            String correo1 = correoNuevo.getText().toString().trim();
            String correo2 = repetirCorreoNuevo.getText().toString().trim();

            if (!correo1.equals(correo2)) {
                Toast.makeText(this, "Los correos no coinciden", Toast.LENGTH_SHORT).show();
                return;
            }


            usuario.setCorreo(correo1);
        }

        // -------------------------------------
        // VALIDAR Y AÑADIR CONTRASEÑA
        // -------------------------------------
        if (modificaContrasenya) {

            String pass1 = contrasenyaNueva.getText().toString().trim();
            String pass2 = repetirContrasenyaNueva.getText().toString().trim();

            if (!pass1.matches(regexTieneMasDe8Caracteres)||!pass1.matches(regexTieneNumeros)||!pass1.matches(regexTieneMayuscula)||!pass1.matches(regexTieneSimbologia)){
                Toast.makeText(this, "Por favor, introduce una contraseña segura", Toast.LENGTH_SHORT).show();
                return;
            }
            if (!pass1.equals(pass2)) {
                Toast.makeText(this, "Las contraseñas no coinciden", Toast.LENGTH_SHORT).show();
                return;
            }

            usuario.setContrasenya(pass1);
        }

        // -------------------------------------
        // LLAMADA FINAL A LA API
        // -------------------------------------
        putModificarDatos(usuario,this);

        Toast.makeText(this, "Datos enviados para modificar", Toast.LENGTH_SHORT).show();

        // LIMPIAR BANDERAS
        modificaNombre = false;
        modificaApellido = false;
        modificaCorreo = false;
        modificaContrasenya = false;

        // DESHABILITAR CAMPOS DESPUÉS DE GUARDAR
        nombre.setEnabled(false);
        apellidos.setEnabled(false);
        correoNuevo.setEnabled(false);
        repetirCorreoNuevo.setEnabled(false);
        contrasenyaNueva.setEnabled(false);
        repetirContrasenyaNueva.setEnabled(false);
        contrasenyaAntigua.setEnabled(false);
    }
}

package com.example.grupo5.aitherapp.activitysApp;

import static com.example.grupo5.aitherapp.retrofit.LogicaNegocio.putModificarDatos;

import android.content.Context;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.example.grupo5.aitherapp.R;
import com.example.grupo5.aitherapp.pojos.PojoUsuario;

public class PerfilFragment extends Fragment {

    EditText nombre, contrasenyaAntigua, contrasenyaNueva, repetirContrasenyaNueva, correoNuevo, repetirCorreoNuevo;
    ImageView botonModificarNombre, botonModificarCorreo, botonModificarContrasenya;
    Button botonGuardarDatos;

    boolean modificaNombre = false;
    boolean modificaCorreo = false;
    boolean modificaContrasenya = false;

    PojoUsuario usuario = new PojoUsuario();

    // Regex
    String regexTieneMayuscula = "^(?=.*[A-Z]).+$";
    String regexTieneNumeros = "^(?=.*\\d).+$";
    String regexTieneSimbologia = "^(?=.*[$@€!%*?&]).+$";
    String regexTieneMasDe8Caracteres = "^.{8,}$";

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_perfil, container, false);

        // 1. VINCULAR VISTAS
        nombre = view.findViewById(R.id.editarNombre);
        correoNuevo = view.findViewById(R.id.editarCorreoNuevo);
        repetirCorreoNuevo = view.findViewById(R.id.editarRepetirCorreoNuevo);
        contrasenyaAntigua = view.findViewById(R.id.editarContrasenyaActual);
        contrasenyaNueva = view.findViewById(R.id.editarContrasenyaNueva);
        repetirContrasenyaNueva = view.findViewById(R.id.editarRepetirContrasenyaNueva);

        botonModificarNombre = view.findViewById(R.id.modificarNombre);
        botonModificarCorreo = view.findViewById(R.id.modificarCorreo);
        botonModificarContrasenya = view.findViewById(R.id.modificarContrasenyaAntigua);
        botonGuardarDatos = view.findViewById(R.id.guardarDatos);

        // 2. CONFIGURAR CLICS
        botonModificarNombre.setOnClickListener(v -> activarModificarNombre());
        botonModificarCorreo.setOnClickListener(v -> activarModificarCorreo());
        botonModificarContrasenya.setOnClickListener(v -> activarModificarContrasenya());
        botonGuardarDatos.setOnClickListener(v -> guardarModificaciones());

        // 3. CARGAR DATOS
        SharedPreferences prefs = getActivity().getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);
        String nombreGuardado = prefs.getString("nombre", "");
        String apellidosGuardados = prefs.getString("apellidos", "");
        nombre.setText(nombreGuardado + " " + apellidosGuardados); // Nombre completo
        correoNuevo.setText(prefs.getString("correo", ""));
        repetirCorreoNuevo.setText(prefs.getString("correo", ""));

        return view;
    }

    // --- MÉTODOS DE LOGICA ---

    private void activarModificarNombre() {
        nombre.setEnabled(!nombre.isEnabled());
        modificaNombre = true;
        habilitarGuardar();
    }

    private void activarModificarCorreo() {
        correoNuevo.setEnabled(!correoNuevo.isEnabled());
        repetirCorreoNuevo.setEnabled(!repetirCorreoNuevo.isEnabled());
        modificaCorreo = true;
        habilitarGuardar();
    }

    private void activarModificarContrasenya() {
        contrasenyaAntigua.setEnabled(!contrasenyaAntigua.isEnabled());
        contrasenyaNueva.setEnabled(!contrasenyaNueva.isEnabled());
        repetirContrasenyaNueva.setEnabled(!repetirContrasenyaNueva.isEnabled());
        modificaContrasenya = true;
        habilitarGuardar();
    }

    private void habilitarGuardar() {
        botonGuardarDatos.setEnabled(true);
    }

    private void guardarModificaciones() {

        SharedPreferences prefs = getActivity().getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);
        SharedPreferences.Editor editor = prefs.edit();

        String idUsuario = prefs.getString("id", "");

        if (idUsuario.isEmpty()) {
            Toast.makeText(getContext(), "ERROR: No se encontró tu ID de usuario", Toast.LENGTH_SHORT).show();
            return;
        }

        usuario.setId(idUsuario);
        usuario.setAction("modificarDatos");

        // --- NOMBRE COMPLETO ---
        if (modificaNombre) {
            String nuevoNombreCompleto = nombre.getText().toString().trim();
            if (nuevoNombreCompleto.isEmpty()) {
                Toast.makeText(getContext(), "El nombre no puede estar vacío", Toast.LENGTH_SHORT).show();
                return;
            }

            // Separar localmente nombre y apellidos
            String[] partes = nuevoNombreCompleto.split(" ", 2);
            String nombreSolo = partes[0];
            String apellidosSolo = partes.length > 1 ? partes[1] : "";

            usuario.setNombre(nombreSolo);
            usuario.setApellidos(apellidosSolo);

            editor.putString("nombre", nombreSolo);
            editor.putString("apellidos", apellidosSolo);
        }

        // --- CORREO ---
        if (modificaCorreo) {
            String correo1 = correoNuevo.getText().toString().trim();
            String correo2 = repetirCorreoNuevo.getText().toString().trim();

            if (!correo1.equals(correo2)) {
                Toast.makeText(getContext(), "Los correos no coinciden", Toast.LENGTH_SHORT).show();
                return;
            }

            usuario.setCorreo(correo1);
            editor.putString("correo", correo1);
        }

        // --- CONTRASEÑA ---
        if (modificaContrasenya) {
            String antigua = contrasenyaAntigua.getText().toString();
            String nueva = contrasenyaNueva.getText().toString();
            String nueva2 = repetirContrasenyaNueva.getText().toString();

            if (!nueva.equals(nueva2)) {
                Toast.makeText(getContext(), "Las contraseñas no coinciden", Toast.LENGTH_SHORT).show();
                return;
            }

            if (!nueva.matches(regexTieneMayuscula) ||
                    !nueva.matches(regexTieneNumeros) ||
                    !nueva.matches(regexTieneSimbologia) ||
                    !nueva.matches(regexTieneMasDe8Caracteres)) {

                Toast.makeText(getContext(), "La nueva contraseña no cumple los requisitos", Toast.LENGTH_SHORT).show();
                return;
            }

            // NO GUARDAR CONTRASEÑA EN SharedPreferences
        }

        // --- ENVIAR A LA API ---
        putModificarDatos(usuario, getContext());

        // --- GUARDAR EN LOCAL ---
        editor.apply();

        Toast.makeText(getContext(), "Datos actualizados correctamente", Toast.LENGTH_SHORT).show();

        // RESET
        deshabilitarTodo();
    }

    private void deshabilitarTodo() {
        nombre.setEnabled(false);
        correoNuevo.setEnabled(false);
        repetirCorreoNuevo.setEnabled(false);
        contrasenyaAntigua.setEnabled(false);
        contrasenyaNueva.setEnabled(false);
        repetirContrasenyaNueva.setEnabled(false);

        botonGuardarDatos.setEnabled(false);

        modificaNombre = false;
        modificaCorreo = false;
        modificaContrasenya = false;
    }
}

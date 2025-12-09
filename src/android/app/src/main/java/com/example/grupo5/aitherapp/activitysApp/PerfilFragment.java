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

    EditText nombre, apellidos, contrasenyaAntigua, contrasenyaNueva, repetirContrasenyaNueva, correoNuevo, repetirCorreoNuevo;
    ImageView botonModificarNombre, botonModificarApellido, botonModificarCorreo, botonModificarContrasenya;
    Button botonGuardarDatos;

    boolean modificaNombre = false;
    boolean modificaApellido = false;
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
        apellidos = view.findViewById(R.id.editarApellidos);
        correoNuevo = view.findViewById(R.id.editarCorreoNuevo);
        repetirCorreoNuevo = view.findViewById(R.id.editarRepetirCorreoNuevo);
        contrasenyaAntigua = view.findViewById(R.id.editarContrasenyaActual);
        contrasenyaNueva = view.findViewById(R.id.editarContrasenyaNueva);
        repetirContrasenyaNueva = view.findViewById(R.id.editarRepetirContrasenyaNueva);

        botonModificarNombre = view.findViewById(R.id.modificarNombre);
        botonModificarApellido = view.findViewById(R.id.modificarApellidos);
        botonModificarCorreo = view.findViewById(R.id.modificarCorreo);
        botonModificarContrasenya = view.findViewById(R.id.modificarContrasenyaAntigua);
        botonGuardarDatos = view.findViewById(R.id.guardarDatos);

        // 2. CONFIGURAR CLICS (Esto sustituye al onClick del XML)
        botonModificarNombre.setOnClickListener(v -> activarModificarNombre());
        botonModificarApellido.setOnClickListener(v -> activarModificarApellidos());
        botonModificarCorreo.setOnClickListener(v -> activarModificarCorreo());
        botonModificarContrasenya.setOnClickListener(v -> activarModificarContrasenya());

        botonGuardarDatos.setOnClickListener(v -> guardarModificaciones());

        // 3. CARGAR DATOS
        SharedPreferences prefs = getActivity().getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);
        nombre.setText(prefs.getString("nombre", ""));
        apellidos.setText(prefs.getString("apellidos", ""));

        return view;
    }

    // --- MÉTODOS DE LÓGICA (Tus mismos métodos de antes) ---

    private void activarModificarNombre() {
        nombre.setEnabled(!nombre.isEnabled());
        modificaNombre = true;
        habilitarGuardar();
    }

    private void activarModificarApellidos() {
        apellidos.setEnabled(!apellidos.isEnabled());
        modificaApellido = true;
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
        String idUsuario = prefs.getString("id", "");
        usuario.setId(idUsuario);
        usuario.setAction("modificarDatos");

        // VALIDACIONES (Tu código original)
        if (modificaNombre) {
            if (nombre.getText().toString().trim().isEmpty()) {
                Toast.makeText(getContext(), "Nombre vacío", Toast.LENGTH_SHORT).show();
                return;
            }
            usuario.setNombre(nombre.getText().toString().trim());
        }

        // ... (Agrega aquí el resto de tus validaciones de apellido, correo y contraseña igual que tenías) ...

        // ENVIAR
        putModificarDatos(usuario, getContext()); // 'getContext()' en vez de 'this'
        Toast.makeText(getContext(), "Datos enviados", Toast.LENGTH_SHORT).show();

        // RESETEAR FORMULARIO
        deshabilitarTodo();
    }

    private void deshabilitarTodo() {
        nombre.setEnabled(false);
        apellidos.setEnabled(false);
        correoNuevo.setEnabled(false);
        repetirCorreoNuevo.setEnabled(false);
        contrasenyaAntigua.setEnabled(false);
        contrasenyaNueva.setEnabled(false);
        repetirContrasenyaNueva.setEnabled(false);

        botonGuardarDatos.setEnabled(false);

        modificaNombre = false;
        modificaApellido = false;
        modificaCorreo = false;
        modificaContrasenya = false;
    }
}
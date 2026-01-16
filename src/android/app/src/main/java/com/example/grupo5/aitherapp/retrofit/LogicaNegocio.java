package com.example.grupo5.aitherapp.retrofit;

import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.util.Log;
import android.widget.Toast;

import com.example.grupo5.aitherapp.activitysApp.HomeActivity;
import com.example.grupo5.aitherapp.pojos.PojoRespuestaServidor;
import com.example.grupo5.aitherapp.pojos.PojoSensor;
import com.example.grupo5.aitherapp.pojos.PojoUsuario;
import com.google.android.gms.common.api.Api;
import com.google.gson.Gson;

import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

//------------------------------------------------------------------
// Fichero: ApiCliente
// Autor: Pablo Chasi
// Fecha: 24/10/2025
//------------------------------------------------------------------
// Clase LogicaNegocio
//
// Descripción:
//  Esta clase se encarga de toda la lógica de negocio de la app móvil
//  al servidor: métodos POST, GET, insert, etc.
//------------------------------------------------------------------
public class LogicaNegocio {

    //-------------------------------------------------------------------------------------------
    // Registro de usuario
    //-------------------------------------------------------------------------------------------
    public static void PostRegistro(String Nombre, String Apellidos, String Correo, String Contrasenya, Context contexto) {
        ApiService api = ApiCliente.getApiService();

        PojoUsuario usuario = new PojoUsuario();
        usuario.setNombre(Nombre);
        usuario.setApellidos(Apellidos);
        usuario.setCorreo(Correo);
        usuario.setContrasenya(Contrasenya);
        usuario.setAction("registrarUsuario");

        Call<PojoRespuestaServidor> call = api.datosRegistro(usuario);

        call.enqueue(new Callback<PojoRespuestaServidor>() {
            @Override
            public void onResponse(Call<PojoRespuestaServidor> call, Response<PojoRespuestaServidor> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    Log.d("Login", "Error en la respuesta: " + response.code());
                    return;
                }

                PojoRespuestaServidor respuesta = response.body();

                if (!"ok".equalsIgnoreCase(respuesta.getStatus())) {
                    Log.w("API", "No funciona: " + respuesta.getMensaje());
                    Toast.makeText(contexto, "Mail ya registrado", Toast.LENGTH_SHORT).show();
                    return;
                }

                Toast.makeText(contexto, respuesta.getMensaje(), Toast.LENGTH_SHORT).show();
            }

            @Override
            public void onFailure(Call<PojoRespuestaServidor> call, Throwable t) {
                Log.e("API", "Error de conexión: " + t.getMessage());
            }
        });
    }

    //-------------------------------------------------------------------------------------------
    // Login de usuario
    //-------------------------------------------------------------------------------------------
    public static void PostLogin(String correo, String contrasenya, Context contexto) {
        ApiService apiService = ApiCliente.getApiService();

        PojoUsuario usuario = new PojoUsuario();
        usuario.setCorreo(correo);
        usuario.setContrasenya(contrasenya);
        usuario.setAction("login");

        Call<PojoRespuestaServidor> call = apiService.loginUsuario(usuario);

        call.enqueue(new Callback<PojoRespuestaServidor>() {
            @Override
            public void onResponse(Call<PojoRespuestaServidor> call, Response<PojoRespuestaServidor> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    Log.d("Login", "Error en la respuesta: " + response.code());
                    return;
                }

                PojoRespuestaServidor respuesta = response.body();

                if (!"ok".equalsIgnoreCase(respuesta.getStatus())) {
                    Log.d("Login", "Login fallido: " + respuesta.getMensaje());
                    return;
                }

                PojoUsuario usuarioServidor = respuesta.getUsuario();
                SharedPreferences prefs = contexto.getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);
                prefs.edit()
                        .putString("id", usuarioServidor.getId())
                        .putString("nombre", usuarioServidor.getNombre())
                        .putString("apellidos", usuarioServidor.getApellidos())
                        .putString("correo", usuarioServidor.getCorreo())
                        .apply();

                usuarioServidor.setAction("getObtenerSensoresUsuario");
                getListaSensores(usuarioServidor, contexto);
            }

            @Override
            public void onFailure(Call<PojoRespuestaServidor> call, Throwable t) {
                Log.e("Login", "Error en conexión: " + t.getMessage());
            }
        });
    }

    //-------------------------------------------------------------------------------------------
    // Modificación de datos del usuario
    //-------------------------------------------------------------------------------------------
    public static void putModificarDatos(PojoUsuario usuario, Context contexto) {
        ApiService apiService = ApiCliente.getApiService();
        Call<PojoRespuestaServidor> call = apiService.modificarDatos(usuario);

        call.enqueue(new Callback<PojoRespuestaServidor>() {
            @Override
            public void onResponse(Call<PojoRespuestaServidor> call, Response<PojoRespuestaServidor> response) {
                if (!response.isSuccessful()) {
                    Log.d("Modificación", "Error al modificar datos");
                    return;
                }

                PojoRespuestaServidor respuesta = response.body();
                PojoUsuario usuarioServidor = respuesta.getUsuario();

                SharedPreferences prefs = contexto.getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);
                prefs.edit()
                        .putString("id", usuarioServidor.getId())
                        .putString("nombre", usuarioServidor.getNombre())
                        .putString("apellidos", usuarioServidor.getApellidos())
                        .putString("correo", usuarioServidor.getCorreo())
                        .apply();

                Toast.makeText(contexto, "Dato modificado exitosamente", Toast.LENGTH_SHORT).show();
            }

            @Override
            public void onFailure(Call<PojoRespuestaServidor> call, Throwable t) {
                Log.e("Login", "Error en conexión: " + t.getMessage());
            }
        });
    }

    //-------------------------------------------------------------------------------------------
    // Vincular sensor
    //-------------------------------------------------------------------------------------------
    public static void postVincularSensor(PojoSensor sensor, Context contexto) {
        ApiService api = ApiCliente.getApiService();
        Call<PojoRespuestaServidor> call = api.vincularSensor(sensor);

        call.enqueue(new Callback<PojoRespuestaServidor>() {
            @Override
            public void onResponse(Call<PojoRespuestaServidor> call, Response<PojoRespuestaServidor> response) {
                if (response.isSuccessful() && response.body() != null) {
                    PojoRespuestaServidor r = response.body();
                    String mensaje = (r.getStatus() != null) ? r.getStatus() : "Añadido tu sensor";
                    Toast.makeText(contexto, mensaje, Toast.LENGTH_SHORT).show();


                    PojoUsuario usuario = new PojoUsuario();
                    usuario.setId(sensor.getUsuario_id());
                    getListaSensores(usuario,contexto);

                } else {
                    Toast.makeText(contexto, "Error HTTP: " + response.code(), Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<PojoRespuestaServidor> call, Throwable t) {
                String mensaje = (t.getMessage() != null) ? t.getMessage() : "Error de conexión desconocido";
                Toast.makeText(contexto, mensaje, Toast.LENGTH_SHORT).show();
            }
        });
    }

    //-------------------------------------------------------------------------------------------
    // Guardar distancia de hoy
    //-------------------------------------------------------------------------------------------
    public static void guardarDistanciaHoy(float distancia, Context contexto) {
        SharedPreferences prefs = contexto.getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);
        String usuarioId = prefs.getString("id", null);

        if (usuarioId == null) {
            Toast.makeText(contexto, "No hay sesión activa", Toast.LENGTH_SHORT).show();
            return;
        }

        ApiService api = ApiCliente.getApiService();
        PojoUsuario data = new PojoUsuario();
        data.setAction("guardarDistancia");
        data.setId(usuarioId);
        data.setDistancia(distancia);

        Call<PojoRespuestaServidor> call = api.guardarDistancia(data);

        call.enqueue(new Callback<PojoRespuestaServidor>() {
            @Override
            public void onResponse(Call<PojoRespuestaServidor> call, Response<PojoRespuestaServidor> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    Toast.makeText(contexto, "Error HTTP: " + response.code(), Toast.LENGTH_SHORT).show();
                    return;
                }

                PojoRespuestaServidor r = response.body();
                Toast.makeText(contexto, r.getMensaje(), Toast.LENGTH_SHORT).show();
            }

            @Override
            public void onFailure(Call<PojoRespuestaServidor> call, Throwable t) {
                Toast.makeText(contexto, "Error: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    //-------------------------------------------------------------------------------------------
    // Obtener historial de distancias
    //-------------------------------------------------------------------------------------------
    public static void getHistorialDistancias(Context contexto, Callback<PojoRespuestaServidor> callback) {
        SharedPreferences prefs = contexto.getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);
        String usuarioId = prefs.getString("id", null);

        if (usuarioId == null) {
            Toast.makeText(contexto, "No hay sesión activa", Toast.LENGTH_SHORT).show();
            return;
        }

        ApiService api = ApiCliente.getApiService();
        PojoUsuario data = new PojoUsuario();
        data.setAction("historialDistancias");
        data.setId(usuarioId);

        Call<PojoRespuestaServidor> call = api.historialDistancias(data);
        call.enqueue(callback);
    }

    //-------------------------------------------------------------------------------------------
    // Obtener distancia por fecha
    //-------------------------------------------------------------------------------------------
    public static void getDistanciaFecha(String fecha, Context contexto, Callback<PojoRespuestaServidor> callback) {
        SharedPreferences prefs = contexto.getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);
        String usuarioId = prefs.getString("id", null);

        if (usuarioId == null) {
            Toast.makeText(contexto, "No hay sesión activa", Toast.LENGTH_SHORT).show();
            return;
        }

        ApiService api = ApiCliente.getApiService();
        PojoUsuario data = new PojoUsuario();
        data.setAction("distanciaFecha");
        data.setId(usuarioId);
        data.setFecha(fecha);

        Call<PojoRespuestaServidor> call = api.distanciaFecha(data);
        call.enqueue(callback);
    }

    /**
     *
     */
    public static void getListaSensores(PojoUsuario usuario, Context contexto) {
        ApiService api = ApiCliente.getApiService();
        Call<PojoRespuestaServidor> call = api.obtenerSensoresUsuario(usuario.getAction(), usuario.getId());

        call.enqueue(new Callback<PojoRespuestaServidor>() {
            @Override
            public void onResponse(Call<PojoRespuestaServidor> call, Response<PojoRespuestaServidor> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    Log.d("Error","Algo ha fallado");
                    return;
                }

                PojoRespuestaServidor respuesta = response.body();
                List<PojoSensor> sensores = respuesta.getListaSensores();

                SharedPreferences prefs = contexto.getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);

                Gson gson = new Gson();
                String jsonSensores = gson.toJson(sensores);

                prefs.edit().putString("ListaSensores", jsonSensores).apply();

                Intent intent = new Intent(contexto, HomeActivity.class);
                contexto.startActivity(intent);
            }

            @Override
            public void onFailure(Call<PojoRespuestaServidor> call, Throwable t) {
                Log.e("Obtener lista", "Error en conexión: " + t.getMessage());
            }
        });
    }

    //-------------------------------------------------------------------------------------------
    // Obtener mediciones para el mapa (NUEVO)
    //-------------------------------------------------------------------------------------------
    public static void getMedicionesMapa(Callback<List<com.example.grupo5.aitherapp.pojos.PojoMedicionMapa>> callback) {
        ApiService api = ApiCliente.getApiService();

        // Llamamos a la acción exacta que tienes en tu index.php
        Call<List<com.example.grupo5.aitherapp.pojos.PojoMedicionMapa>> call = api.getMedicionesMapa("getMediciones");

        call.enqueue(callback);
    }
}

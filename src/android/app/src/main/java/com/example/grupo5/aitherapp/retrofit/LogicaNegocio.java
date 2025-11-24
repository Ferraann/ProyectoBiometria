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
//  Esta clase se encargar de hacer toda la logica de negocio de
//  la app movil al servidor, se declara metodos post,get,insert...
//------------------------------------------------------------------
public class LogicaNegocio {
    //-------------------------------------------------------------------------------------------
    //     Nombre:txt, Apellidos:txt, email:txt, contraseña:txt --> postRegistro()
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

        //Ejecutamos la llamada post de forma asincrona, con un callback.Lo primero que hacemos es cojer la respuesta
        //del servido, al recibirlo comparamos si ha fallado algo y si la respuesta en si tiene cuerpo. Si no se cumple
        //ninguna de estás dos cóndiciones significa que algo a ocurriod en la conexión. Si por el contrario es favorable
        //la respuesta lo metemos en una clase pojo para poder usarlo de forma facil. Si la respuesta es aceptada, en este
        //caso significa que la cuenta de la persona no está creado teniendo en cuenta su email y si no es así es lo contrario
        call.enqueue(new Callback<PojoRespuestaServidor>() {
            @Override
            public void onResponse(Call<PojoRespuestaServidor> call, Response<PojoRespuestaServidor> response) {

                if(!response.isSuccessful()||response.body()==null){
                    Log.d("Login", "Error en la respuesta: " + response.code());
                    return;
                }

                PojoRespuestaServidor respuesta = response.body();

                // Miramos el status que viene del servidor
                if (!"ok".equalsIgnoreCase(respuesta.getStatus())) {
                    Log.w("API", "No funciona: " + respuesta.getMensaje());
                    Toast.makeText(contexto, "Mail ya registrado", Toast.LENGTH_SHORT).show();
                    return;
                }



                Toast.makeText(contexto,  respuesta.getMensaje() , Toast.LENGTH_SHORT).show();

//                SharedPreferences prefs = contexto.getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);
//                prefs.edit()
//                        .putString("id", usuarioServidor.getId())
//                        .putString("nombre", usuarioServidor.getNombre())
//                        .putString("apellidos", usuarioServidor.getApellidos())
//                        // adapta "getCorreo" o "getGmail" según lo que tengas
//                        .putString("correo", usuarioServidor.getCorreo())
//                        .apply();

            }

            @Override
            public void onFailure(Call<PojoRespuestaServidor> call, Throwable t) {
                Log.e("API", "Error de conexión: " + t.getMessage());
            }
        });

    }


    //-------------------------------------------------------------------------------------------
    //     Email:txt, Contraseña:txt, Contexto:context --> postRegistro()
    //-------------------------------------------------------------------------------------------
    public static void PostLogin(String correo, String contrasenya, Context contexto){
        ApiService apiService = ApiCliente.getApiService();


        PojoUsuario usuario = new PojoUsuario();
        usuario.setCorreo(correo);
        usuario.setContrasenya(contrasenya);
        usuario.setAction("login");


        Call<PojoRespuestaServidor> call = apiService.loginUsuario(usuario);


        call.enqueue(new Callback<PojoRespuestaServidor>() {
            @Override
            public void onResponse(Call<PojoRespuestaServidor> call, Response<PojoRespuestaServidor> response) {
                if(!response.isSuccessful()||response.body()==null){
                    Log.d("Login", "Error en la respuesta: " + response.code());
                    return;

                }
                PojoRespuestaServidor respuesta = response.body();

                // Miramos el status que viene del servidor
                if (!"ok".equalsIgnoreCase(respuesta.getStatus())) {
                    // Si viene un mensaje de error, lo mostramos en log (o Toast)
                    Log.d("Login", "Login fallido: " + respuesta.getMensaje());
                    return;
                }
                PojoUsuario usuarioServidor = respuesta.getUsuario();
                // 4.4 Guardamos los datos del usuario en SharedPreferences
                SharedPreferences prefs = contexto.getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);
                prefs.edit()
                        .putString("id", usuarioServidor.getId())
                        .putString("nombre", usuarioServidor.getNombre())
                        .putString("apellidos", usuarioServidor.getApellidos())
                        .putString("correo", usuarioServidor.getCorreo())
                        .apply();

                Intent intent = new Intent(contexto, HomeActivity.class);
                contexto.startActivity(intent);
            }

            @Override
            public void onFailure(Call<PojoRespuestaServidor> call, Throwable t) {
                Log.e("Login", "Error en conexión: " + t.getMessage());
            }
        });
    }

    //--------------------------------------------------------------------------------
    //  Nombre: txt, Apellidos: txt, Email: txt,
    //--------------------------------------------------------------------------------
    public static void putModificarDatos(PojoUsuario usuario,Context contexto) {
        ApiService apiService = ApiCliente.getApiService(); // Usamos tu ApiCliente existente
        Call<PojoRespuestaServidor> call = apiService.modificarDatos(usuario);

        call.enqueue(new Callback<PojoRespuestaServidor>() {
            @Override
            public void onResponse(Call<PojoRespuestaServidor> call, Response<PojoRespuestaServidor> response) {
                if (!response.isSuccessful()) {
                    Log.d("Modifcación", "Error al modificar datos");
                    return;
                }

                PojoRespuestaServidor respuesta = response.body();
                PojoUsuario usuarioServidor = respuesta.getUsuario();
                // 4.4 Guardamos los datos del usuario en SharedPreferences
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

    //--------------------------------------------------------------------------------
    //  sensor: PojoSensor, contexto: Context
    //--------------------------------------------------------------------------------
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
                    Intent intent = new Intent(contexto, HomeActivity.class);
                    contexto.startActivity(intent);
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


}
//---------------------------------------------------------------
//---------------------------------------------------------------
//--------------------------------------------------------------

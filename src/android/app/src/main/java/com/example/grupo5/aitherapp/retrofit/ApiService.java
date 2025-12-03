package com.example.grupo5.aitherapp.retrofit;

import com.example.grupo5.aitherapp.pojos.PojoRespuestaServidor;
import com.example.grupo5.aitherapp.pojos.PojoSensor;
import com.example.grupo5.aitherapp.pojos.PojoUsuario;
import com.google.gson.JsonObject;

import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.Field;
import retrofit2.http.FormUrlEncoded;
import retrofit2.http.POST;
import retrofit2.http.Query;

// ------------------------------------------------------------------
// Fichero: ApiCliente
// Autor: Pablo Chasi
// Fecha: 24/10/2025
// ------------------------------------------------------------------
// Interfaz ApiService
//
// Descripción:
//  Interfaz donde se declaran las peticiones HTTP y las variables que
//  se usarán y enviarán.
// ------------------------------------------------------------------
public interface ApiService {

    // Registro de usuario
    @POST("index.php")
    Call<PojoRespuestaServidor> datosRegistro(@Body PojoUsuario usuario);

    // Envío de datos de CO2 y Temperatura (form-urlencoded)
    @FormUrlEncoded
    @POST("index.php")
    Call<Void> enviarDatos(
            @Field("CO2") float co2,
            @Field("Temperatura") float temperatura
    );

    // Login de usuario
    @POST("index.php")
    Call<PojoRespuestaServidor> loginUsuario(@Body PojoUsuario usuario);

    // Modificación de datos del usuario
    @POST("index.php")
    Call<PojoRespuestaServidor> modificarDatos(@Body PojoUsuario usuario);

    // Vinculación de un sensor al usuario
    @POST("index.php")
    Call<PojoRespuestaServidor> vincularSensor(@Body PojoSensor sensor);

    // Crear sensor y relación
    @POST("index.php")
    Call<JsonObject> crearSensorYRelacion(
            @Query("accion") String accion,
            @Body JsonObject body
    );

    // Obtener sensores de un usuario
    @POST("index.php")
    Call<JsonObject> obtenerSensoresUsuario(@Body JsonObject body);

    // Guardar distancia del usuario
    @POST("index.php")
    Call<PojoRespuestaServidor> guardarDistancia(@Body PojoUsuario data);

    // Obtener historial de distancias
    @POST("index.php")
    Call<PojoRespuestaServidor> historialDistancias(@Body PojoUsuario data);

    // Obtener distancia de una fecha específica
    @POST("index.php")
    Call<PojoRespuestaServidor> distanciaFecha(@Body PojoUsuario data);

}

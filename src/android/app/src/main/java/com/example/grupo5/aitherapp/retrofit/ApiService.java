package com.example.grupo5.aitherapp.retrofit;

import com.example.grupo5.aitherapp.pojos.PojoRespuestaServidor;
import com.example.grupo5.aitherapp.pojos.PojoSensor;
import com.example.grupo5.aitherapp.pojos.PojoUsuario;
import com.google.gson.JsonObject;

import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.Field;
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
//  Interfaz donde se declara las peticiones HTTP y las variables que
//  se usaran y enviaran.
// ------------------------------------------------------------------
public interface ApiService {
    //Metodo post para hacer el login
    //lo que se pretende es enviar y no
    //recibir al tener void.

    @POST("index.php")
    Call<PojoRespuestaServidor> datosRegistro(
            @Body PojoUsuario usuario
    );

    //Metodo post donde se muestra la extructura de enviar datos
    @POST ("index.php")
    Call<Void> enviarDatos(
            @Field("CO2") float co2,
            @Field("Temperatura") float temperatura
    );

    //Metodo post para hacer login del usuario
    @POST("index.php")
    Call<PojoRespuestaServidor> loginUsuario(@Body PojoUsuario usuario);

    //Metodo post para modificar datos
    @POST("index.php") // Cambia al endpoint real en tu servidor
    Call<PojoRespuestaServidor> modificarDatos(@Body PojoUsuario usuario);

    //Metodo post vara crear el vinculo del sensor
    @POST("index.php")
    Call<PojoRespuestaServidor> vincularSensor(@Body PojoSensor sensor);

    //Metodo post crear sensor y relacion
    @POST("index.php")
    Call<JsonObject> crearSensorYRelacion(
            @Query("accion") String accion,
            @Body JsonObject body);


    @POST("index.php")
    Call<JsonObject> obtenerSensoresUsuario(@Body JsonObject body);
}

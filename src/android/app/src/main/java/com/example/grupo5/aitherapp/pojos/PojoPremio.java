package com.example.grupo5.aitherapp.pojos;

public class PojoPremio {
    private String nombre;
    private int coins;
    private int imagenResId;

    public PojoPremio(String nombre, int coins, int imagenResId) {
        this.nombre = nombre;
        this.coins = coins;
        this.imagenResId = imagenResId;
    }

    public String getNombre() {
        return nombre;
    }

    public int getCoins() {
        return coins;
    }

    public int getImagenResId() {
        return imagenResId;
    }
}

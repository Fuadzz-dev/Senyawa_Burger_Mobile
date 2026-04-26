package com.example.codecraftstudioapp.models;

import java.io.Serializable;

public class CartItem implements Serializable {
    private int id;
    private String name;
    private double price;
    private int qty;
    private String notes;
    private String image;

    public CartItem(int id, String name, double price, int qty, String notes, String image) {
        this.id = id;
        this.name = name;
        this.price = price;
        this.qty = qty;
        this.notes = notes;
        this.image = image;
    }

    public int getId() { return id; }
    public String getName() { return name; }
    public double getPrice() { return price; }
    public int getQty() { return qty; }
    public String getNotes() { return notes; }
    public String getImage() { return image; }
    
    public void setQty(int qty) { this.qty = qty; }
    public void setNotes(String notes) { this.notes = notes; }
}

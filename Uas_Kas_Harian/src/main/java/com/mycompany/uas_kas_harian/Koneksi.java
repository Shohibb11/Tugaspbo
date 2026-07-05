package com.mycompany.uas_kas_harian;
import java.sql.*;
import javax.swing.JOptionPane;

public class Koneksi {
    private static Connection koneksi;
    public static Connection getKoneksi() {
        if (koneksi == null) {
            try {
                String url = "jdbc:mysql://localhost:3306/db_kas_harian";
                String user = "root";
                String pass = ""; 
                DriverManager.registerDriver(new com.mysql.cj.jdbc.Driver());
                koneksi = DriverManager.getConnection(url, user, pass);
            } catch (SQLException e) {
                JOptionPane.showMessageDialog(null, "Koneksi Gagal: " + e.getMessage());
            }
        }
        return koneksi;
    }
}
import React from "react";
import { Document, Page, Text, View, StyleSheet } from "@react-pdf/renderer";

const styles = StyleSheet.create({
  page: { padding: 30 },
  section: { marginBottom: 10 },
  title: { fontSize: 18, marginBottom: 10 },
  label: { fontWeight: "bold" },
});

const BookingDetailsPDF = ({ booking }) => {
  return (
    <Document>
      <Page size="A4" style={styles.page}>
        <Text style={styles.title}>Booking Details</Text>
        <View style={styles.section}>
          <Text><Text style={styles.label}>Hall:</Text> {booking.hall?.name}</Text>
          <Text><Text style={styles.label}>Date:</Text> {booking.booking_date}</Text>
          <Text><Text style={styles.label}>Shift:</Text> {booking.shift}</Text>
          <Text><Text style={styles.label}>Status:</Text> {booking.status}</Text>
          <Text><Text style={styles.label}>Club Account:</Text> {booking.member?.club_account || "N/A"}</Text>
          <Text><Text style={styles.label}>Email:</Text> {booking.member?.email || "N/A"}</Text>
        </View>
      </Page>
    </Document>
  );
};

export default BookingDetailsPDF;

import React from "react";
import { Document, Page, Text, View, StyleSheet } from "@react-pdf/renderer";

// Simple styles for PDF
const styles = StyleSheet.create({
  page: {
    backgroundColor: "#232323",
    color: "#fff",
    padding: 24,
    fontSize: 12,
    fontFamily: "Helvetica"
  },
  title: {
    fontSize: 20,
    color: "#BFA465",
    textAlign: "center",
    marginBottom: 12,
    fontWeight: "bold"
  },
  hallName: {
    fontSize: 16,
    color: "#BFA465",
    textAlign: "center",
    marginBottom: 8,
    fontWeight: "bold"
  },
  section: {
    marginBottom: 16,
    padding: 12,
    border: "1px solid #B18E4E",
    borderRadius: 6,
    backgroundColor: "#333"
  },
  row: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginBottom: 4
  },
  label: {
    fontWeight: "bold"
  },
  footer: {
    marginTop: 32,
    borderTop: "1px solid #444",
    paddingTop: 8,
    fontSize: 10,
    color: "#BFA465",
    textAlign: "center"
  }
});

const BookingDetailsPDF = ({
  hall,
  member,
  displayStatus,
  fullAmount,
  prebookAmount,
  remaining,
  booking_date,
  shiftLabel
}) => (
  <Document>
    <Page size="A4" style={styles.page}>
      <Text style={styles.title}>Booking Details</Text>
      <Text style={styles.hallName}>{hall.name}</Text>
      <View style={styles.section}>
        <View style={styles.row}>
          <Text style={styles.label}>Name:</Text>
          <Text>{member.name}</Text>
        </View>
        <View style={styles.row}>
          <Text style={styles.label}>Club Account:</Text>
          <Text>{member.club_account}</Text>
        </View>
        <View style={styles.row}>
          <Text style={styles.label}>Status:</Text>
          <Text>{displayStatus}</Text>
        </View>
        <View style={styles.row}>
          <Text style={styles.label}>Total Amount:</Text>
          <Text>{fullAmount}tk</Text>
        </View>
        <View style={styles.row}>
          <Text style={styles.label}>Pre Booking Amount:</Text>
          <Text>{prebookAmount}tk</Text>
        </View>
        <View style={styles.row}>
          <Text style={styles.label}>Amount to be Paid:</Text>
          <Text>{remaining}tk</Text>
        </View>
        <View style={styles.row}>
          <Text style={styles.label}>Date:</Text>
          <Text>{booking_date}</Text>
        </View>
        <View style={styles.row}>
          <Text style={styles.label}>Shift:</Text>
          <Text>{shiftLabel}</Text>
        </View>
      </View>
      <Text style={styles.footer}>
        Gulshan Club Ltd. | House: NWJ-2/A, Bir Uttom Sultan Mahmud Road (Old Road: 50), Gulshan 2, Dhaka 1212, Bangladesh | mail@gulshanclub.com | 16717
        {"\n"}
        Copyright © 2024 Gulshan Club Limited. All rights reserved.
        {"\n"}
        Designed & developed by Workspace Infotech LTD
      </Text>
    </Page>
  </Document>
);

export default BookingDetailsPDF;
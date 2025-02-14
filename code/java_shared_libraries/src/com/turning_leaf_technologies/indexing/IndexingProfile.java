package com.turning_leaf_technologies.indexing;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.text.SimpleDateFormat;

import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.logging.log4j.Logger;

public class IndexingProfile extends BaseIndexingSettings {
	private char callNumberCutterSubfield;
	private char callNumberPoststampSubfield;
	private char volume;
	private char itemUrl;
	private char totalRenewalsSubfield;
	private char iCode2Subfield;
	private char lastYearCheckoutsSubfield;
	private char barcodeSubfield;
	private String itemTag ;
	private char itemRecordNumberSubfield;
	private String lastCheckinFormat;
	private SimpleDateFormat lastCheckinFormatter;
	private String dateCreatedFormat;
	private SimpleDateFormat dateCreatedFormatter;
	private String dueDateFormat;
	private char lastCheckinDateSubfield;
	private char locationSubfield;
	private char itemStatusSubfield;
	private char iTypeSubfield;
	private char collectionSubfield;
	private char shelvingLocationSubfield;
	private char yearToDateCheckoutsSubfield;
	private char totalCheckoutsSubfield;
	private char callNumberSubfield;
	private char dateCreatedSubfield;
	private char dueDateSubfield;
	private SimpleDateFormat dueDateFormatter;
	private char eContentDescriptor = ' ';
	private boolean doAutomaticEcontentSuppression;
	private char format;
	private long lastUpdateFromMarcExport;
	private long fullMarcExportRecordIdThreshold;
	private long lastVolumeExportTimestamp;
	private boolean checkRecordForLargePrint;
	private char subLocationSubfield;
	private int determineAudienceBy;
	private char audienceSubfield;
	private long lastUpdateOfAuthorities;
	private long lastChangeProcessed;

	private void setFilenamesToInclude(String filenamesToInclude) {
		this.filenamesToInclude = filenamesToInclude;
	}

	private void setGroupingClass(String groupingClass) {
		this.groupingClass = groupingClass;
	}

	public String getSpecifiedFormatCategory() {
		return specifiedFormatCategory;
	}

	private void setSpecifiedFormatCategory(String specifiedFormatCategory) {
		this.specifiedFormatCategory = specifiedFormatCategory;
	}

	public String getFormatSource() {
		return formatSource;
	}

	private void setFormatSource(String formatSource) {
		this.formatSource = formatSource;
	}

	public char getFormat() {
		return format;
	}

	public void setFormat(char format) {
		this.format = format;
	}

	public static IndexingProfile loadIndexingProfile(Connection dbConn, String profileToLoad, Logger logger) {
		//Get the Indexing Profile from the database
		IndexingProfile indexingProfile = new IndexingProfile();
		try {
			PreparedStatement getIndexingProfileStmt = dbConn.prepareStatement("SELECT * FROM indexing_profiles where name ='" + profileToLoad + "'");
			ResultSet indexingProfileRS = getIndexingProfileStmt.executeQuery();
			if (indexingProfileRS.next()) {

				indexingProfile.setId(indexingProfileRS.getLong("id"));
				indexingProfile.setName(indexingProfileRS.getString("name"));
				indexingProfile.setFilenamesToInclude(indexingProfileRS.getString("filenamesToInclude"));
				indexingProfile.setMarcPath(indexingProfileRS.getString("marcPath"));
				indexingProfile.setMarcEncoding(indexingProfileRS.getString("marcEncoding"));
				indexingProfile.setRecordNumberTag(indexingProfileRS.getString("recordNumberTag"));
				indexingProfile.setRecordNumberSubfield(getCharFromRecordSet(indexingProfileRS, "recordNumberSubfield"));
				indexingProfile.setRecordNumberPrefix(indexingProfileRS.getString("recordNumberPrefix"));
				indexingProfile.setItemTag(indexingProfileRS.getString("itemTag"));
				indexingProfile.setItemRecordNumberSubfield(getCharFromRecordSet(indexingProfileRS,"itemRecordNumber"));
				indexingProfile.setLastCheckinDateSubfield(getCharFromRecordSet(indexingProfileRS,"lastCheckinDate"));
				indexingProfile.setLastCheckinFormat(indexingProfileRS.getString("lastCheckinFormat"));
				indexingProfile.setLocationSubfield(getCharFromRecordSet(indexingProfileRS,"location"));
				indexingProfile.setItemStatusSubfield(getCharFromRecordSet(indexingProfileRS,"status"));
				indexingProfile.setDueDateSubfield(getCharFromRecordSet(indexingProfileRS,"dueDate"));
				indexingProfile.setDueDateFormat(indexingProfileRS.getString("dueDateFormat"));
				indexingProfile.setDateCreatedSubfield(getCharFromRecordSet(indexingProfileRS,"dateCreated"));
				indexingProfile.setDateCreatedFormat(indexingProfileRS.getString("dateCreatedFormat"));
				indexingProfile.setCallNumberSubfield(getCharFromRecordSet(indexingProfileRS,"callNumber"));
				indexingProfile.setTotalCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS,"totalCheckouts"));
				indexingProfile.setYearToDateCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS,"yearToDateCheckouts"));

				indexingProfile.setIndividualMarcPath(indexingProfileRS.getString("individualMarcPath"));
				indexingProfile.setName(indexingProfileRS.getString("name"));
				indexingProfile.setNumCharsToCreateFolderFrom(indexingProfileRS.getInt("numCharsToCreateFolderFrom"));
				indexingProfile.setCreateFolderFromLeadingCharacters(indexingProfileRS.getBoolean("createFolderFromLeadingCharacters"));

				indexingProfile.setShelvingLocationSubfield(getCharFromRecordSet(indexingProfileRS,"shelvingLocation"));
				indexingProfile.setITypeSubfield(getCharFromRecordSet(indexingProfileRS,"iType"));
				indexingProfile.setCollectionSubfield(getCharFromRecordSet(indexingProfileRS,"collection"));
				indexingProfile.setSubLocationSubfield(getCharFromRecordSet(indexingProfileRS,"subLocation"));

				indexingProfile.setGroupingClass(indexingProfileRS.getString("groupingClass"));
				indexingProfile.setFormatSource(indexingProfileRS.getString("formatSource"));
				indexingProfile.setSpecifiedFormatCategory(indexingProfileRS.getString("specifiedFormatCategory"));
				indexingProfile.setFormat(getCharFromRecordSet(indexingProfileRS, "format"));
				indexingProfile.setCheckRecordForLargePrint(indexingProfileRS.getBoolean("checkRecordForLargePrint"));

				indexingProfile.setDoAutomaticEcontentSuppression(indexingProfileRS.getBoolean("doAutomaticEcontentSuppression"));
				indexingProfile.setEContentDescriptor(getCharFromRecordSet(indexingProfileRS, "eContentDescriptor"));

				indexingProfile.setLastYearCheckoutsSubfield(getCharFromRecordSet(indexingProfileRS, "lastYearCheckouts"));
				indexingProfile.setBarcodeSubfield(getCharFromRecordSet(indexingProfileRS, "barcode"));
				if (indexingProfile.getItemRecordNumberSubfield() == ' '){
					indexingProfile.setItemRecordNumberSubfield(indexingProfile.getBarcodeSubfield());
				}
				indexingProfile.setTotalRenewalsSubfield(getCharFromRecordSet(indexingProfileRS, "totalRenewals"));
				indexingProfile.setICode2Subfield(getCharFromRecordSet(indexingProfileRS, "iCode2"));

				indexingProfile.setCallNumberCutterSubfield(getCharFromRecordSet(indexingProfileRS, "callNumberCutter"));
				indexingProfile.setCallNumberPoststampSubfield(getCharFromRecordSet(indexingProfileRS, "callNumberPoststamp"));
				indexingProfile.setVolume(getCharFromRecordSet(indexingProfileRS, "volume"));
				indexingProfile.setItemUrl(getCharFromRecordSet(indexingProfileRS, "itemUrl"));

				indexingProfile.setDetermineAudienceBy(indexingProfileRS.getInt("determineAudienceBy"));
				indexingProfile.setAudienceSubfield(getCharFromRecordSet(indexingProfileRS, "audienceSubfield"));

				indexingProfile.setLastUpdateOfChangedRecords(indexingProfileRS.getLong("lastUpdateOfChangedRecords"));
				indexingProfile.setLastUpdateOfAllRecords(indexingProfileRS.getLong("lastUpdateOfAllRecords"));
				indexingProfile.setLastUpdateFromMarcExport(indexingProfileRS.getLong("lastUpdateFromMarcExport"));
				indexingProfile.setFullMarcExportRecordIdThreshold(indexingProfileRS.getLong("fullMarcExportRecordIdThreshold"));
				indexingProfile.setLastVolumeExportTimestamp(indexingProfileRS.getLong("lastVolumeExportTimestamp"));
				indexingProfile.setLastUpdateOfAuthorities(indexingProfileRS.getLong("lastUpdateOfAuthorities"));
				indexingProfile.setLastChangeProcessed(indexingProfileRS.getLong("lastChangeProcessed"));

				indexingProfile.setRunFullUpdate(indexingProfileRS.getBoolean("runFullUpdate"));
				indexingProfile.setRegroupAllRecords(indexingProfileRS.getBoolean("regroupAllRecords"));
			} else {
				logger.error("Unable to find " + profileToLoad + " indexing profile, please create a profile with the name ils.");
			}

		}catch (Exception e){
			logger.error("Error reading index profile " + profileToLoad, e);
		}
		return indexingProfile;
	}

	private void setAudienceSubfield(char audienceSubfield) {
		this.audienceSubfield = audienceSubfield;
	}

	public char getAudienceSubfield(){
		return this.audienceSubfield;
	}

	public String getItemTag() {
		return itemTag;
	}

	public void setItemTag(String itemTag) {
		this.itemTag = itemTag;
	}

	public void setId(Long id) {
		this.id = id;
	}

	public void setName(String name) {
		this.name = name;
	}

	private void setIndividualMarcPath(String individualMarcPath) {
		this.individualMarcPath = individualMarcPath;
	}

	private void setNumCharsToCreateFolderFrom(int numCharsToCreateFolderFrom) {
		this.numCharsToCreateFolderFrom = numCharsToCreateFolderFrom;
	}

	private void setCreateFolderFromLeadingCharacters(boolean createFolderFromLeadingCharacters) {
		this.createFolderFromLeadingCharacters = createFolderFromLeadingCharacters;
	}

	private void setRecordNumberTag(String recordNumberTag) {
		this.recordNumberTag = recordNumberTag;
	}

	public char getItemRecordNumberSubfield() {
		return itemRecordNumberSubfield;
	}

	private void setItemRecordNumberSubfield(char itemRecordNumberSubfield) {
		this.itemRecordNumberSubfield = itemRecordNumberSubfield;
	}

	public String getLastCheckinFormat() {
		return lastCheckinFormat;
	}

	private void setLastCheckinFormat(String lastCheckinFormat) {
		this.lastCheckinFormat = lastCheckinFormat;
		this.lastCheckinFormatter = new SimpleDateFormat(lastCheckinFormat);
	}

	public String getDateCreatedFormat() {
		return dateCreatedFormat;
	}

	private void setDateCreatedFormat(String dateCreatedFormat) {
		this.dateCreatedFormat = dateCreatedFormat;
		dateCreatedFormatter = new SimpleDateFormat(dateCreatedFormat);
	}

	public String getDueDateFormat() {
		return dueDateFormat;
	}

	private void setDueDateFormat(String dueDateFormat) {
		this.dueDateFormat = dueDateFormat;
		this.dueDateFormatter = new SimpleDateFormat(dueDateFormat);
	}

	public char getLastCheckinDateSubfield() {
		return lastCheckinDateSubfield;
	}

	private void setLastCheckinDateSubfield(char lastCheckinDateSubfield) {
		this.lastCheckinDateSubfield = lastCheckinDateSubfield;
	}

	public char getLocationSubfield() {
		return locationSubfield;
	}

	private void setLocationSubfield(char locationSubfield) {
		this.locationSubfield = locationSubfield;
	}

	public char getItemStatusSubfield() {
		return itemStatusSubfield;
	}

	private void setItemStatusSubfield(char itemStatusSubfield) {
		this.itemStatusSubfield = itemStatusSubfield;
	}

	public char getITypeSubfield() {
		return iTypeSubfield;
	}

	private void setITypeSubfield(char iTypeSubfield) {
		this.iTypeSubfield = iTypeSubfield;
	}

	public char getShelvingLocationSubfield() {
		return shelvingLocationSubfield;
	}

	private void setShelvingLocationSubfield(char shelvingLocationSubfield) {
		this.shelvingLocationSubfield = shelvingLocationSubfield;
	}

	public char getYearToDateCheckoutsSubfield() {
		return yearToDateCheckoutsSubfield;
	}

	private void setYearToDateCheckoutsSubfield(char yearToDateCheckoutsSubfield) {
		this.yearToDateCheckoutsSubfield = yearToDateCheckoutsSubfield;
	}

	public char getTotalCheckoutsSubfield() {
		return totalCheckoutsSubfield;
	}

	private void setTotalCheckoutsSubfield(char totalCheckoutsSubfield) {
		this.totalCheckoutsSubfield = totalCheckoutsSubfield;
	}

	public char getCallNumberSubfield() {
		return callNumberSubfield;
	}

	private void setCallNumberSubfield(char callNumberSubfield) {
		this.callNumberSubfield = callNumberSubfield;
	}

	public char getDateCreatedSubfield() {
		return dateCreatedSubfield;
	}

	private void setDateCreatedSubfield(char dateCreatedSubfield) {
		this.dateCreatedSubfield = dateCreatedSubfield;
	}

	public char getDueDateSubfield() {
		return dueDateSubfield;
	}

	private void setDueDateSubfield(char dueDateSubfield) {
		this.dueDateSubfield = dueDateSubfield;
	}

	public void setMarcPath(String marcPath) {
		this.marcPath = marcPath;
	}

	private void setMarcEncoding(String marcEncoding) {
		this.marcEncoding = marcEncoding;
	}

	private void setRecordNumberPrefix(String recordNumberPrefix) {
		this.recordNumberPrefix = recordNumberPrefix;
	}

	public boolean isDoAutomaticEcontentSuppression() {
		return doAutomaticEcontentSuppression;
	}

	private void setDoAutomaticEcontentSuppression(boolean doAutomaticEcontentSuppression) {
		this.doAutomaticEcontentSuppression = doAutomaticEcontentSuppression;
	}

	public char getEContentDescriptor() {
		return eContentDescriptor;
	}

	private void setEContentDescriptor(char eContentDescriptor) {
		this.eContentDescriptor = eContentDescriptor;
	}

	public boolean useEContentSubfield() {
		return this.eContentDescriptor != ' ';
	}

	public SimpleDateFormat getDueDateFormatter() {
		return dueDateFormatter;
	}

	public SimpleDateFormat getDateCreatedFormatter() {
		return dateCreatedFormatter;
	}

	public SimpleDateFormat getLastCheckinFormatter() {
		return lastCheckinFormatter;
	}

	public char getLastYearCheckoutsSubfield() {
		return lastYearCheckoutsSubfield;
	}

	private void setLastYearCheckoutsSubfield(char lastYearCheckoutsSubfield) {
		this.lastYearCheckoutsSubfield = lastYearCheckoutsSubfield;
	}

	public char getBarcodeSubfield() {
		return barcodeSubfield;
	}

	private void setBarcodeSubfield(char barcodeSubfield) {
		this.barcodeSubfield = barcodeSubfield;
	}

	public char getTotalRenewalsSubfield() {
		return totalRenewalsSubfield;
	}

	private void setTotalRenewalsSubfield(char totalRenewalsSubfield) {
		this.totalRenewalsSubfield = totalRenewalsSubfield;
	}

	public char getICode2Subfield() {
		return iCode2Subfield;
	}

	private void setICode2Subfield(char iCode2Subfield) {
		this.iCode2Subfield = iCode2Subfield;
	}

	public char getCallNumberCutterSubfield() {
		return callNumberCutterSubfield;
	}

	private void setCallNumberCutterSubfield(char callNumberCutterSubfield) {
		this.callNumberCutterSubfield = callNumberCutterSubfield;
	}

	public char getCallNumberPoststampSubfield() {
		return callNumberPoststampSubfield;
	}

	private void setCallNumberPoststampSubfield(char callNumberPoststampSubfield) {
		this.callNumberPoststampSubfield = callNumberPoststampSubfield;
	}

	public char getVolume() {
		return volume;
	}

	private void setVolume(char volume) {
		this.volume = volume;
	}

	public char getItemUrl() {
		return itemUrl;
	}

	private void setItemUrl(char itemUrl) {
		this.itemUrl = itemUrl;
	}

	private void setRecordNumberSubfield(char recordNumberSubfield) {
		this.recordNumberSubfield = recordNumberSubfield;
	}

	private void setLastUpdateOfChangedRecords(long lastUpdateOfChangedRecords) {
		this.lastUpdateOfChangedRecords = lastUpdateOfChangedRecords;
	}

	private void setLastUpdateOfAllRecords(long lastUpdateOfAllRecords) {
		this.lastUpdateOfAllRecords = lastUpdateOfAllRecords;
	}

	private void setRunFullUpdate(boolean runFullUpdate) {
		this.runFullUpdate = runFullUpdate;
	}

	private void setRegroupAllRecords(boolean regroupAllRecords) {
		this.regroupAllRecords = regroupAllRecords;
	}

	private void setLastUpdateFromMarcExport(long lastUpdateFromMarcExport) {
		this.lastUpdateFromMarcExport = lastUpdateFromMarcExport;
	}

	public long getLastUpdateFromMarcExport() {
		return lastUpdateFromMarcExport;
	}

	private void setCheckRecordForLargePrint(boolean checkRecordForLargePrint) {
		this.checkRecordForLargePrint = checkRecordForLargePrint;
	}

	public boolean getCheckRecordForLargePrint() {
		return checkRecordForLargePrint;
	}

	private void setCollectionSubfield(char collectionSubfield) {
		this.collectionSubfield = collectionSubfield;
	}

	public char getCollectionSubfield() {
		return collectionSubfield;
	}

	private void setSubLocationSubfield(char sublocationSubfield) {
		this.subLocationSubfield = sublocationSubfield;
	}

	public char getSubLocationSubfield() {
		return subLocationSubfield;
	}

	public int getDetermineAudienceBy() {
		return determineAudienceBy;
	}

	private void setDetermineAudienceBy(int determineAudienceBy) {
		this.determineAudienceBy = determineAudienceBy;
	}

	public long getLastVolumeExportTimestamp() {
		return lastVolumeExportTimestamp;
	}

	public void setLastVolumeExportTimestamp(long lastVolumeExportTimestamp) {
		this.lastVolumeExportTimestamp = lastVolumeExportTimestamp;
	}

	public long getLastUpdateOfAuthorities() {
		return lastUpdateOfAuthorities;
	}

	private void setLastUpdateOfAuthorities(long lastUpdateOfAuthorities) {
		this.lastUpdateOfAuthorities = lastUpdateOfAuthorities;
	}

	public void clearRegroupAllRecords(Connection dbConn, BaseLogEntry logEntry) {
		try {
			PreparedStatement clearRegroupAllRecordsStmt = dbConn.prepareStatement("UPDATE indexing_profiles set regroupAllRecords = 0 where id =?");
			clearRegroupAllRecordsStmt.setLong(1, id);
			clearRegroupAllRecordsStmt.executeUpdate();
		}catch (Exception e){
			logEntry.incErrors("Could not clear regroup all records", e);
		}
	}
	public long getFullMarcExportRecordIdThreshold() {
		return fullMarcExportRecordIdThreshold;
	}

	public void setFullMarcExportRecordIdThreshold(long fullMarcExportRecordIdThreshold) {
		this.fullMarcExportRecordIdThreshold = fullMarcExportRecordIdThreshold;
	}

	public long getLastChangeProcessed() {
		return lastChangeProcessed;
	}

	public void setLastChangeProcessed(long lastChangeProcessed) {
		this.lastChangeProcessed = lastChangeProcessed;
	}
	public void updateLastChangeProcessed(Connection dbConn, BaseLogEntry logEntry) {
		try {
			PreparedStatement updateLastChangeProcessedId = dbConn.prepareStatement("UPDATE indexing_profiles set lastChangeProcessed = ? where id =?");
			updateLastChangeProcessedId.setLong(1, lastChangeProcessed);
			updateLastChangeProcessedId.setLong(2, id);
			updateLastChangeProcessedId.executeUpdate();
		}catch (Exception e){
			logEntry.incErrors("Could not set last record processed", e);
		}
	}
}

package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;

class LionRecordProcessor extends IIIRecordProcessor {
	LionRecordProcessor(GroupedWorkIndexer indexer, Connection dbConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, dbConn, indexingProfileRS, logger, fullReindex);

		loadOrderInformationFromExport();

		validCheckedOutStatusCodes.add("&");
		validCheckedOutStatusCodes.add("c");
		validCheckedOutStatusCodes.add("o");
		validCheckedOutStatusCodes.add("y");
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		String status = itemInfo.getStatusCode();
		String dueDate = itemInfo.getDueDate() == null ? "" : itemInfo.getDueDate();
		String availableStatus = "-&couvy";
		if (status.length() > 0 && availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0) {
				available = true;
			}
		}

		return available;
	}

	protected boolean determineLibraryUseOnly(ItemInfo itemInfo, Scope curScope) {
		return itemInfo.getStatusCode().equals("o");
	}

	protected boolean isItemSuppressed(DataField curItem) {
		Subfield icode2Subfield = curItem.getSubfield(iCode2Subfield);
		if (icode2Subfield != null) {
			String icode2 = icode2Subfield.getData().toLowerCase().trim();

			//Suppress icode2 of n
			if (icode2.equals("n")) {
				return true;
			}
		}

		return super.isItemSuppressed(curItem);
	}

	protected boolean isBibSuppressed(Record record) {
		DataField field907 = record.getDataField("998");
		if (field907 != null){
			Subfield suppressionSubfield = field907.getSubfield('e');
			if (suppressionSubfield != null){
				String bCode3 = suppressionSubfield.getData().toLowerCase().trim();
				if (bCode3.equals("n")){
					logger.debug("Bib record is suppressed due to bcode3 " + bCode3);
					return true;
				}
			}
		}
		return super.isBibSuppressed(record);
	}

	@Override
	public void loadPrintFormatInformation(RecordInfo recordInfo, Record record) {
		HashMap<String, Integer> itemCountsByItype = new HashMap<>();
		HashMap<String, String> itemTypeToFormat = new HashMap<>();
		int mostUsedCount = 0;
		String mostPopularIType = "";		//Get a list of all the formats based on the items
		List<DataField> items = MarcUtil.getDataFields(record, itemTag);
		for(DataField item : items){
			Subfield iTypeSubField = item.getSubfield(iTypeSubfield);
			if (iTypeSubField != null){
				String iType = iTypeSubField.getData().toLowerCase();
				if (itemCountsByItype.containsKey(iType)){
					itemCountsByItype.put(iType, itemCountsByItype.get(iType) + 1);
				}else{
					itemCountsByItype.put(iType, 1);
					//Translate the iType to see what formats we get.  Some item types do not have a format by default and use the default translation
					//We still will want to record those counts.
					String translatedFormat = translateValue("format", iType, recordInfo.getRecordIdentifier());
					itemTypeToFormat.put(iType, translatedFormat);
				}

				if (itemCountsByItype.get(iType) > mostUsedCount){
					mostPopularIType = iType;
					mostUsedCount = itemCountsByItype.get(iType);
				}
			}
		}

		if (itemTypeToFormat.size() == 0 || itemTypeToFormat.get(mostPopularIType) == null || itemTypeToFormat.get(mostPopularIType).length() == 0){
			//We didn't get any formats from the collections, get formats from the base method (007, 008, etc).
			//logger.debug("All formats are books or there were no formats found, loading format information from the bib");
			super.loadPrintFormatFromBib(recordInfo, record);
		} else{
			//logger.debug("Using default method of loading formats from iType");
			recordInfo.addFormat(itemTypeToFormat.get(mostPopularIType));
			String translatedFormatCategory = translateValue("format_category", mostPopularIType, recordInfo.getRecordIdentifier());
			if (translatedFormatCategory != null) {
				recordInfo.addFormatCategory(translatedFormatCategory);
			}
			long formatBoost = 1L;
			String formatBoostStr = translateValue("format_boost", mostPopularIType, recordInfo.getRecordIdentifier());
			if (formatBoostStr == null){
				formatBoostStr = translateValue("format_boost", itemTypeToFormat.get(mostPopularIType), recordInfo.getRecordIdentifier());
			}
			if (StringUtils.isNumeric(formatBoostStr)) {
				formatBoost = Long.parseLong(formatBoostStr);
			}
			recordInfo.setFormatBoost(formatBoost);
		}
	}

	protected boolean isOrderItemValid(String status, String code3) {
		return status.equals("o") || status.equals("1") || status.equals("q") || status.equals("a");
	}
}
